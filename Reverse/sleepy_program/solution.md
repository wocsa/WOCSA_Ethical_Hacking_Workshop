# Sleepy Program
Solving this challenge only requires to avoid sleeping too much. Even though this could be achieved by patching the binary to avoid calling sleep, the goal is to introduce the participants to dynamic linking.
The intended solution is to modify the LD_PRELOAD environment variable to force using our own sleep function, which does nothing.

To do so, compile the sleep.c file as a shared object (`-shared`), and execute the program that way:
```bash
LD_PRELOAD=./sleep.so ./a.out # one liner that only modifies the environment variable for the created process

# if you want to change the environment variable for the entire shell, here are the commands:
export LD_PRELOAD=./sleep.so
./a.out
```