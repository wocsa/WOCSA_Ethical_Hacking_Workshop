/*
 * sysinfo.c — vulnerable SUID binary for Lab 12
 *
 * Calls 'service' and 'ps' without absolute paths.
 * Since it's SUID root, a hijacked PATH lets an
 * attacker execute arbitrary code as root.
 */
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>

int main(void) {
    printf("=== System Information ===\n");
    printf("Services:\n");
    system("service --status-all 2>&1 | head -5");   /* relative path! */
    printf("\nProcesses:\n");
    system("ps aux | head -10");                      /* relative path! */
    printf("==========================\n");
    return 0;
}
