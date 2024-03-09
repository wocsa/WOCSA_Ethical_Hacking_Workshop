#include <stdio.h>
#include <unistd.h>
#include <string.h>
#include <time.h>
int sleep_count = 0;
int initial = 0;
int elapsed = 0;
char *table = "NEaT8Pl7g4Nwf4d!3gx";
char flag[12];
int curr_idx = -30;
int table_idx = 0;
void print_slowmo(char *input)
{
    for (int i = 0; i < strlen(input); i++)
    {
        printf("%c", input[i]);
        fflush(stdout);
        usleep(sleep_count);
        sleep_count += 10000;
        elapsed = time(NULL) - initial;
        curr_idx++;
        if (curr_idx < 13 && curr_idx >= 0)
        {
            flag[curr_idx] = table[table_idx];
            table_idx = ((table_idx * 2) + 1 + elapsed) % strlen(table);
        }
    }
}
int main(int argc, char const *argv[])
{
    initial = time(NULL); //TODO
    print_slowmo("Hello! I know you are here to get the flag, but you can't get it that easily!\n");
    print_slowmo("I think that I am too slow to decypher it in the correct way\n");
    printf("This is the flag that I computed for you: ");
    printf("%s\n", flag);
    return 0;
}
