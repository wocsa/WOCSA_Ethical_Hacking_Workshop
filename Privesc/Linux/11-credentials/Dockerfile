FROM ubuntu:20.04

# ─────────────────────────────────────────────
# Lab 11 · Credentials in config files
# Goal    : A web app config leaks a database
#           password. Root reuses that password.
# Hint    :
#   find / -name "*.php" -o -name "*.conf" -o -name ".env" 2>/dev/null
#   grep -rn "password\|passwd\|pass" /var/www/ /opt/ 2>/dev/null
#   su root   (try all found passwords)
# ─────────────────────────────────────────────

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get install -y --no-install-recommends openssh-server && \
    rm -rf /var/lib/apt/lists/*

# Set root password — same as the one in the config file
RUN echo "root:Sup3rS3cr3t!2024" | chpasswd

RUN useradd -m -s /bin/bash wocsa && echo "wocsa:Fz2bSOCu9dII" | chpasswd

RUN echo "WOCSA{credential_reuse_is_bad}" > /root/flag.txt && \
    chmod 600 /root/flag.txt

# Plant credentials in realistic locations
RUN mkdir -p /var/www/html /opt/app

# WordPress-style config
RUN cat > /var/www/html/wp-config.php << 'EOF'
<?php
define('DB_NAME', 'wordpress');
define('DB_USER', 'wpuser');
define('DB_PASSWORD', 'Sup3rS3cr3t!2024');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('table_prefix', 'wp_');
EOF

# .env style config
RUN cat > /opt/app/.env << 'EOF'
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appdb
DB_USERNAME=appuser
DB_PASSWORD=Sup3rS3cr3t!2024
MAIL_HOST=smtp.mailtrap.io
EOF

# Bash history with a past su attempt (another hint)
RUN echo "mysql -u root -pSup3rS3cr3t!2024 wordpress" > /home/wocsa/.bash_history && \
    chown wocsa:wocsa /home/wocsa/.bash_history

RUN echo "=== Lab 11: Credential hunting ===" > /home/wocsa/README.txt && \
    echo "This server runs a web application. Developers sometimes leave" >> /home/wocsa/README.txt && \
    echo "passwords in config files — and reuse them." >> /home/wocsa/README.txt && \
    echo "Hunt for credentials in /var/www/, /opt/, bash history..." >> /home/wocsa/README.txt && \
    echo "Goal: read /root/flag.txt" >> /home/wocsa/README.txt && \
    chown wocsa:wocsa /home/wocsa/README.txt

WORKDIR /home/wocsa
RUN mkdir -p /var/run/sshd
CMD ["/usr/sbin/sshd", "-D"]
