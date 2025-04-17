# Warning
This workshop is for educational purposes only. Ethical hacking is conducted with the explicit permission of the system owner to improve security.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
- [Workshop](#workshop)
  - [Setting Up the Environment](#setting-up-the-environment)
  - [Exploring Juice Shop](#exploring-juice-shop)
  - [Identifying Vulnerabilities](#identifying-vulnerabilities)
  - [Exploiting Vulnerabilities](#exploiting-vulnerabilities)
  - [Defense](#defense)
- [Quick Win](#quick-win)
- [Conclusion](#conclusion)
- [Resources](#resources)

# Introduction

The OWASP Juice Shop project is an intentionally insecure web application designed for security training, awareness demos, Capture The Flag (CTF) events, and as a testbed for security tools. It encompasses vulnerabilities from the entire OWASP Top Ten along with many other security flaws found in real-world applications. This workshop will guide you through setting up Juice Shop, identifying vulnerabilities, exploiting them, and understanding defensive measures.

**Key Features of OWASP Juice Shop:**

- **Comprehensive Vulnerabilities:** Juice Shop includes a wide range of vulnerabilities, covering the OWASP Top Ten and many other real-world security flaws.
- **Interactive Learning:** The application provides an interactive and gamified approach to learning about web application security.
- **Real-World Scenarios:** Juice Shop simulates real-world scenarios, making it an excellent tool for practical training.
- **Open Source:** Being open-source, Juice Shop can be freely used and modified for educational purposes.

<p style="text-align:center;">
  <img src="https://owasp.org/assets/images/logo.png" alt="Juice Shop Logo"/>
</p>

# Workshop

For this workshop, ensure you have the necessary permissions and environment to run Juice Shop. The following steps will guide you through the process.

## Setting Up the Environment

1. **Install Docker:**
   Juice Shop can be easily set up using Docker. If you don't have Docker installed, you can download and install it from [Docker's official website](https://www.docker.com/).

2. **Run Juice Shop:**
   Open a terminal and run the following command to start Juice Shop:
   ```bash
   docker run --rm -p 3000:3000 bkimminich/juice-shop
   ```
   This command will pull the Juice Shop image from Docker Hub and run it on port 3000.

3. **Access Juice Shop:**
   Open your web browser and navigate to `http://localhost:3000` to access the Juice Shop application.

## Exploring Juice Shop

1. **Navigate the Application:**
   Spend some time exploring the Juice Shop application. Familiarize yourself with the different features and functionalities.

2. **Score Board:**
   Juice Shop includes a score board that tracks your progress in finding and exploiting vulnerabilities. Access the score board by clicking on the trophy icon in the top-right corner.

## Identifying Vulnerabilities

1. **OWASP Top Ten:**
   Juice Shop includes vulnerabilities from the OWASP Top Ten. Use the score board as a guide to identify these vulnerabilities.

2. **Additional Flaws:**
   Besides the OWASP Top Ten, Juice Shop includes many other security flaws. Explore the application to identify these additional vulnerabilities.

## Exploiting Vulnerabilities

For detailed steps on exploiting the vulnerabilities in Juice Shop, refer to the comprehensive write-up available on GitHub:

- [Juice Shop Write-up](https://github.com/Whyiest/Juice-Shop-Write-up)

This write-up provides a step-by-step guide to identifying and exploiting various vulnerabilities in Juice Shop, including SQL injection, XSS, CSRF, and more.

## Defense

1. **Input Validation:**
   Implement input validation to prevent SQL injection and XSS attacks. Ensure that all user inputs are properly sanitized.

2. **Secure Configuration:**
   Configure your web application securely. Use strong passwords, disable unnecessary features, and apply security patches regularly.

3. **Regular Audits:**
   Conduct regular security audits to identify and fix vulnerabilities. Use tools like OWASP ZAP or Burp Suite for automated testing.

# Quick Win

1. **Run Juice Shop:**
   ```bash
   docker run --rm -p 3000:3000 bkimminich/juice-shop
   ```
2. **Access Juice Shop:**
   ```bash
   http://localhost:3000
   ```
3. **Explore Vulnerabilities:**
   Use the score board to guide you through identifying and exploiting vulnerabilities.

# Conclusion

In this workshop, you have been introduced to the OWASP Juice Shop project, learned about web application security, and gained practical knowledge about identifying and exploiting vulnerabilities. Having understood the vulnerabilities, you have learned to implement defensive measures to secure your web applications.

Ethical hacking is a valuable skill when used responsibly and with explicit authorization from system owners. Continue to explore and apply these techniques in an ethical and legal manner to enhance your cybersecurity knowledge and contribute to a safer digital environment.

# Resources

- [OWASP Juice Shop GitHub Repository](https://github.com/bkimminich/juice-shop)
- [OWASP Top Ten](https://owasp.org/www-project-top-ten/)
- [Burp Suite](https://portswigger.net/burp)
- [OWASP ZAP](https://www.zaproxy.org/)
- [Juice Shop Write-up](https://github.com/Whyiest/Juice-Shop-Write-up)