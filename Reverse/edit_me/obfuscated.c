#include <stdio.h>
#include <stdlib.h>
#include <string.h>

void forgotten()
{
    unsigned char secret[] = {0xcb, 0x13, 0x78, 0x2b, 0x19, 0xd0, 0x73, 0x40, 0x2b, 0x33, 0x73, 0x70, 0x0b, 0xa8, 0x13, 0xb8, 0x13, 0x23, 0xa8};
    int length = sizeof(secret) / sizeof(secret[0]);
    char result[length];
    for (int i = 0; i < length; i++)
    {
        result[i] = (char)secret[i];
    }
    int shift = 3;
    int secret_len = strlen(secret);
    int i = 0;
    int j = 0;
    int k = 0;
    while (i < secret_len)
    {
        j = 0;
        k = i + 2;
        i = j + k - 1;
        while (j < shift)
        {
            secret[k - 2] = (secret[k - 2] - 24) & 0xff;
            j++;
        }
        secret[k - 2] = secret[k - 2] ^ 0x42;
        secret[k - 2] = ((secret[k - 2] & 0xFF) >> shift) | ((secret[k - 2] & 0xFF) << (8 - shift));
    }
    printf("This is the flag you are looking for: %s\n", secret);
}

int main(int argc, char const *argv[])
{
    printf("Hey there! How are you doing?\n");
    int i = 7;
    int j = 8;
    for (int k = 0; k < 10; k++)
    {
        i = i + j;
        j = j + i;
    }
    printf("I managed to calculate %d and %d!\n", i, j);
    printf("Are you proud of me? :)\n");
    return 0;
}
