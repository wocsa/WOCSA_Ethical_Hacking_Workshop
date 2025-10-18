# Python Jails Challenge

## Overview
This is a collection of Python sandbox escape challenges. Each jail has different restrictions and security mechanisms that you need to bypass to capture the flag.

## Network Access

Replace `192.168.X.X` with your host machine's IP address.

### Website
**Access:** `http://192.168.X.X:8000`

The main website for the challenge.

---

## Jail 1

**Access:** `nc 192.168.X.X 9001`

### Code:
```python
def jail(banned_words):
    command = str(input(">>>"))
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        result=eval(command)
        print(result)
    except Exception as e:
        print(f"Invalid command, {e}")

while True:
    jail(["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__", "breakpoint", "subprocess"])
```

### Restrictions:
- Banned words: `exec`, `eval`, `builtins`, `.`, `getattr`, `globals`, `locals`, `import`, `os`, `open`, `__`, `breakpoint`
- Uses `eval()` to execute commands
- Prints the result

---

## Jail 2

**Access:** `nc 192.168.X.X 9002`

### Code:
```python
history = []

def add2history(history, command):
    history.append((command))

def run(command):
    add2history(history, command)
    eval(command) 

def jail():
    global history
    banned_words = ["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__", "breakpoint", "subprocess"]
    command = input(">>>")
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        run(command)
    except Exception as e:
        print(f"Invalid command, {e}")

while True:
    jail()
```

### Restrictions:
- Banned words: `exec`, `eval`, `builtins`, `.`, `getattr`, `globals`, `locals`, `import`, `os`, `open`, `__`, `breakpoint`
- Maintains command history
- Uses `eval()` through `run()` function

---

## Jail 3

**Access:** `nc 192.168.X.X 9003`

### Code:
```python
import os
last_size = os.path.getsize(__file__)
del os

def auto_reload():
    import os
    global last_size
    new_size = os.path.getsize(__file__)
    if new_size != last_size:
        last_size = new_size
        with open(__file__, 'r') as f:
            code = f.read()
        print("File size changed, reloading...")
        exec(code, globals())
    del os

def jail():
    banned_words = ["exec", "eval", "builtins", "getattr", "globals", "locals", "import", "os", "__", "breakpoint", "subprocess"]
    command = str(input(">>>"))
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        result=eval(command)
        print(result)
    except Exception as e:
        print(f"Invalid command, {e}")

while True:
    jail()
    auto_reload()
```

### Restrictions:
- Banned words: `exec`, `eval`, `builtins`, `getattr`, `globals`, `locals`, `import`, `os`, `__`, `breakpoint`
- Has auto-reload functionality that monitors file size changes
- Uses `eval()` to execute commands
- Prints the result

---

## Jail 4

**Access:** `nc 192.168.X.X 9004`

### Code:
```python
def jail():
    banned_words = ["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__", "subprocess"]
    command = str(input(">>>"))
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        eval(command)
    except:
        print("Invalid command")

while True:
    jail()
```

### Restrictions:
- Banned words: `exec`, `eval`, `builtins`, `.`, `getattr`, `globals`, `locals`, `import`, `os`, `open`, `__`
- Note: `breakpoint` is NOT banned
- Uses `eval()` to execute commands

---

## Jail 5

**Access:** `nc 192.168.X.X 9005`

### Code:
```python
def jail():
    banned_words = ["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__", "breakpoint", "subprocess"]
    command = str(input(">>>"))
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        result=eval(command)
        print(result)
    except Exception as e:
        print(f"Invalid command, {e}")

while True:
    jail()
```

### Restrictions:
- Banned words: `exec`, `eval`, `builtins`, `.`, `getattr`, `globals`, `locals`, `import`, `os`, `open`, `__`, `breakpoint`
- Uses `eval()` to execute commands
- Prints the result

---

## Tips

- Each jail uses Python's `eval()` function with different restrictions
- Look for creative ways to bypass the banned word filters
- Consider alternative Python syntax and built-in functions
- Think about how to access restricted modules without using banned keywords
- Some jails have unique features that might be exploitable

Good luck!
