# Forgotten function
To solve this challenge, force the binary to execute the function "forgotten". To do so, there are multiple possibilities:
- within Ghidra, patch the binary: change a printf call to a forgotten call. To save the newly produced binary, search online for "savepatch.py ghidra"
- in gdb, change rip at any point for the beginning of the function.