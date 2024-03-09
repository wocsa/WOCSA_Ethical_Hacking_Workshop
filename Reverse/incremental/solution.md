# Incremental
This binary illustrates a vulnerability that arises when checking character after character a password.
If N is the alphabet size and M the length of the password, the bruteforce can be linearized so that only N*M tries are required (in comparison to N^M for a normal bruteforce).
As this challenge requires a little bit of programming, a solver is required.
The solver tries each possibility in the alphabet for each character one by one. 

### To accelerate the execution of the script, make sure to disable gdb-peda or gef, as their verbosity drastically slows down the execution.