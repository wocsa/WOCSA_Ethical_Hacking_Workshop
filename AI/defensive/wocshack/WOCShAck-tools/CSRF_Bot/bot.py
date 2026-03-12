import time
import os
import requests
from bs4 import BeautifulSoup
from playwright.sync_api import sync_playwright

class TicketMonitor:
    def __init__(self, username, password, url):
        self.base_url = url
        self.login_url = f"{self.base_url}/index.php?page=user/login.php"
        self.ticket_url = f"{self.base_url}/index.php?page=support/ticket.php"
        self.credentials = {"username": username, "password": password}
        self.session = requests.Session()
        self.processed_links = set()
        self.initial_ticket_ids = set()
        self.headers = {
            "Content-Type": "application/x-www-form-urlencoded",
            "User-Agent": "Mozilla/5.0",
            "Accept": "text/html",
            "Accept-Language": "en-US,en;q=0.9",
            "Origin": self.base_url,
            "Referer": self.ticket_url,
            "Connection": "keep-alive"
        }

    def establish_session(self):
        try:
            response = self.session.post(
                self.login_url,
                data=self.credentials,
                timeout=10
            )
            return "logout" in response.text.lower()
        except requests.exceptions.RequestException as e:
            print(f"Connection error: {e}")
            return False

    def fetch_page_content(self, url):
        try:
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            return response.text
        except requests.exceptions.RequestException as e:
            print(f"Page fetch error ({url}): {e}")
            return None

    def extract_tickets(self, html_content):
        try:
            soup = BeautifulSoup(html_content, 'html.parser')
            items = soup.select("ul.list-group.list-group-flush > li.list-group-item")
            data = []

            for item in items:
                ticket_id_input = item.find('input', {'name': 'ticket_id'})
                ticket_id = ticket_id_input.get('value') if ticket_id_input else None
                if not ticket_id:
                    continue

                title_element = item.find('strong')
                title = title_element.get_text(strip=True) if title_element else "Unknown"

                link_element = item.find('a', string="Link to Issue", attrs={'target': '_blank'})
                link = link_element.get('href') if link_element else None

                csrf_token_input = item.find('input', {'name': 'csrf_token'})
                csrf_token = csrf_token_input.get('value') if csrf_token_input else None

                data.append({
                    'id': ticket_id,
                    'title': title,
                    'link': link,
                    'csrf_token': csrf_token
                })

            return data
        except Exception as e:
            print(f"Ticket extraction error: {e}")
            return []

    def capture_initial_state(self):
        ticket_html = self.fetch_page_content(self.ticket_url)
        if ticket_html:
            tickets = self.extract_tickets(ticket_html)
            self.initial_ticket_ids = {ticket['id'] for ticket in tickets}
            self.processed_links.update(ticket['link'] for ticket in tickets if ticket['link'])
        print(f"Captured initial state: {len(self.initial_ticket_ids)} Tickets")

    def process_ticket_link(self, url):
        print(f"Processing ticket link: {url}")
        for attempt in range(2):
            try:
                with sync_playwright() as p:
                    browser = p.chromium.launch(headless=True)
                    page = browser.new_page()

                    page.goto(self.login_url, timeout=30000)
                    page.fill('input[name="username"]', self.credentials["username"])
                    page.fill('input[name="password"]', self.credentials["password"])
                    page.click('button[type="submit"]')
                    page.wait_for_load_state("networkidle", timeout=30000)

                    if "logout" in page.content().lower():
                        print("Authentication successful for ticket link.")
                        page.goto(url, timeout=30000)
                        page.wait_for_load_state("networkidle", timeout=30000)
                        print(f"Successfully processed ticket link: {url}")
                        self.processed_links.add(url)
                        browser.close()
                        return True
                    else:
                        print(f"Authentication failed for ticket link (attempt {attempt + 1}).")
                        browser.close()
                        return False
            except Exception as e:
                print(f"Error processing ticket link {url} (attempt {attempt + 1}): {e}")
                if attempt == 1:
                    print(f"Failed to process ticket link: {url}")
                    return False
                time.sleep(2)

    def respond(self, ticket_id, has_link, csrf_token=None):
        response_text = (
            "Hi, I'm the intern, the admin gave me his account. I clicked on the link you sent, but I don't understand what to do with it."
            if has_link else
            "I have received your message, I am on leave, I will review it later."
        )

        data = {
            "ticket_id": ticket_id,
            "ticket_comment": response_text,
            "csrf_token": csrf_token,
            "submit_ticket_comment": ""
        }

        try:
            response = self.session.post(self.ticket_url, headers=self.headers, data=data, timeout=10)
            response.raise_for_status()
            print(f"Responded to Ticket {ticket_id} with: {response_text}")
        except requests.exceptions.RequestException as e:
            print(f"Response error for Ticket {ticket_id}: {e}")

        if csrf_token:
            status_data = {
                "ticket_id": ticket_id,
                "status": "in_progress",
                "csrf_token": csrf_token,
                "update_ticket": ""
            }
            try:
                response = self.session.post(self.ticket_url, headers=self.headers, data=status_data, timeout=10)
                response.raise_for_status()
                print(f"Updated ticket {ticket_id} status to in_progress")
            except requests.exceptions.RequestException as e:
                print(f"Status update error for Ticket {ticket_id}: {e}")

    def execute_cycle(self):
        ticket_changes = False

        try:
            ticket_html = self.fetch_page_content(self.ticket_url)
            if ticket_html:
                tickets = self.extract_tickets(ticket_html)
                ticket_links = [ticket['link'] for ticket in tickets if ticket['link']]
                print(f"Detected ticket links: {ticket_links if ticket_links else 'None'}")
                new_tickets = [ticket for ticket in tickets if ticket['id'] not in self.initial_ticket_ids]

                if new_tickets:
                    ticket_changes = True
                    for ticket in new_tickets:
                        ticket_id = ticket['id']
                        print(f"New ticket detected: ID={ticket_id}, Title={ticket['title']}")
                        has_link = bool(ticket['link'])
                        csrf_token = ticket['csrf_token']

                        if ticket['link']:
                            print(f"Found link for ticket {ticket_id}: {ticket['link']}")
                            self.process_ticket_link(ticket['link'])

                        if csrf_token:
                            self.respond(ticket_id, has_link, csrf_token)
                        else:
                            print(f"Skipping response for ticket {ticket_id}: No CSRF token")

                        self.initial_ticket_ids.add(ticket_id)
        except Exception as e:
            print(f"Ticket monitoring error: {e}")

        print(f"monitoring, changes in ticket: {ticket_changes}")
        return ticket_changes

    def run_monitoring(self):
        if not self.establish_session():
            print("Authentication failed")
            return

        print("Monitoring started")
        self.capture_initial_state()

        try:
            while True:
                new_activity = self.execute_cycle()
                print(f"Waiting 10s... Activity: {new_activity}")
                time.sleep(10)
        except KeyboardInterrupt:
            print("Monitoring stopped")

if __name__ == "__main__":
    username = os.getenv("USERNAME")
    password = os.getenv("PASSWORD")
    url = os.getenv("URL")

    if username and password and url:
        time.sleep(60)
        monitor = TicketMonitor(username, password, url)
        monitor.run_monitoring()
    else:
        print("Missing credentials or URL")