# WOCSA CyberRange - user workstation
# Ubuntu XFCE desktop streamed to the browser (Selkies), plus the workshop
# toolset used across the CyberRange workshops.
FROM lscr.io/linuxserver/webtop:ubuntu-xfce

# Workshop tools layer
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
      curl \
      wget \
      net-tools \
      iputils-ping \
      traceroute \
      nmap \
      tcpdump \
      netcat-openbsd \
      socat \
      python3 \
      jq \
      unzip \
      git \
      nano \
      vim \
    && rm -rf /var/lib/apt/lists/*