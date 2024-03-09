#include <stdio.h>
#include <string.h>
#include <stdlib.h>
char calculate_value(char input, int seed)
{
    int result = (int)input;
    for (int i = 0; i < seed; i++)
    {
        result = (result - 24) & 0xff;
    }
    result = result ^ 0x42;
    return (char)result;
}
int main(int argc, char const *argv[])
{
    unsigned char answer[] = {
    0x06, 0x7c, 0x7f, 0x78, 0x55, 0x44, 0xbe, 0x38,
    0xbd, 0x38, 0xb9, 0xa5, 0x90, 0xd1, 0x3c, 0x19,
    0x5f, 0xbe, 0x93, 0x8f, 0xed, 0xeb, 0xe6, 0x87,
    0xe1, 0x86, 0x8d, 0x80, 0xea, 0xed, 0xeb, 0xe6,
    0xed, 0x8f, 0x88, 0x85, 0xfa, 0xfc, 0xea, 0x8b,
    0x86, 0xe5, 0xe3, 0x9b, '\0' 
};
    // TH15_CH4773NG3_W4S_N0T_345Y_8UT_Y0U_M4D3_1T
    int length = sizeof(answer) / sizeof(answer[0]);
    char result[length];
    for (int i = 0; i < length; i++)
    {
        result[i] = (char)answer[i];
    }
    char input[64];
    printf("Enter the flag: ");
    fgets(input, 64, stdin);
    char cypher[64];
    memset(cypher, 0, sizeof(cypher));
    int seed = 54;
    for (int i = 0; i < strlen(input); i++)
    {
        if (i == 0)
        {
            cypher[i] = calculate_value(input[i], seed);
        }
        else
        {
            cypher[i] = calculate_value((char)input[i] ^ cypher[i - 1], seed);
        }
        if ((unsigned char)cypher[i] != (unsigned char)answer[i])
        {
            printf("Try again!\n");
            exit(0);
        }
    }
    printf("You found the flag!\n");
    return 0;
}
