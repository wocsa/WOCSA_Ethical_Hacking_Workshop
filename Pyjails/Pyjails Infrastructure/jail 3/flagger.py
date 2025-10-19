import requests
import hashlib


def ask_server(name):

    url = f"http://website:5000/get_jail_uuid/{name}"
    response = requests.get(url)
    if response.status_code == 200:
        # Parse JSON response
        data = response.json()
        try:
            return data.get("uuid")
        except Exception as e:
            print(e)
            return -1
            
    else:
        print("Failed to get data:", response.status_code)

def convert(name):
    name = str(name)
    return name.replace(" ", "%20")

def make_flag(jail_id):
    import os
    client_ip = os.environ.get('NCAT_REMOTE_ADDR')
    del os
    combined = jail_id + client_ip
    return hashlib.sha256(combined.encode()).hexdigest()[:10]
    

def get_flag(name):
   response = ask_server(convert(name))
   if response != -1:
    return "Flag = " + make_flag(response)

