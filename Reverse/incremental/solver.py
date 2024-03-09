#!/usr/bin/env python3
# run this script through gdb by typing `source solver.py`
p = ['a']*43
chars = [i for i in range(0x21, 0x7f)] # assume the password is composed of printable chars
list_idx = 0
currchar = 0 # idx of the current evaluated char
nb_continue= 0
gdb.execute("set confirm off")
gdb.execute("set pagination off")
gdb.execute("file ./a.out")
gdb.execute("b *0x000055555555542b")
for i in range(10000):
    # set stdin to pass
    with open("pass", "w") as f:
        f.write("".join(p))
    gdb.execute("r < pass")
    for i in range(nb_continue):
        gdb.execute("c")
    c = gdb.parse_and_eval("$eflags")
    if "ZF" not in str(c):
        # the current letter is incorrect
        p[currchar] = chr(chars[list_idx])
        list_idx = (list_idx + 1) % len(chars)
    else:
        print("".join(p))
        currchar += 1
        list_idx = 0
        nb_continue = nb_continue + 1
    gdb.execute("k")