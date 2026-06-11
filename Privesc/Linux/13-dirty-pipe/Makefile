# Makefile — CVE-2022-0847 Dirty Pipe lab

CC     = gcc
CFLAGS = -Wall -Wextra -O2

.PHONY: all clean

all: dirtypipe
	@echo "[*] Build complete."
	@echo "[*] Run: ./dirtypipe /usr/local/bin/suid_target"

dirtypipe: dirtypipe.c
	$(CC) $(CFLAGS) -o dirtypipe dirtypipe.c

clean:
	rm -f dirtypipe
