#include <stdio.h>
#include <string.h>
int main(int argc, char const *argv[])
{
    char input[32];
    printf("Enter the flag: ");
    fgets(input, 32, stdin);
    unsigned char secret[] = {0x23, 0xc8, 0x23, 0xa0, 0x30, 0x28, 0x13, 0x0b, 0x78, 0x73, 0x28, 0x13, 0x70, 0x23, 0x73, 0x13, 0x33, 0x73, 0x38, 0x23, 0x18, 0xd0, 0x73, 0x23, 0xc8, 0xa0, 0x13, 0x28, 0x13, 0x78, 0xc0, 0x0a};
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
    secret[strlen(secret) - 1] = '\0';
    if (strcmp(input, secret) == 0)
    {
        printf("You found the flag!\n");
    }
    else
    {
        printf("Try again!\n");
    }
    return 0;
}
