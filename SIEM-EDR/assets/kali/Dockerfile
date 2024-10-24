# Use a specific version of the Ubuntu base image
FROM kalilinux/kali-rolling

RUN apt update && apt install -y openssh-server sudo nmap hydra

RUN useradd -rm -d /home/ubuntu -s /bin/bash -g root -G sudo -u 1000 kali

RUN  echo 'kali:kali' | chpasswd

RUN wget https://github.com/zacheller/rockyou/raw/refs/heads/master/rockyou.txt.tar.gz

RUN tar -xf rockyou.txt.tar.gz

RUN service ssh start

# Copy the start script into the container
COPY start-kali.sh /root/start-kali.sh

# Make the script executable
RUN chmod +x /root/start-kali.sh

# Command to run the start script
CMD ["/root/start-kali.sh"]
