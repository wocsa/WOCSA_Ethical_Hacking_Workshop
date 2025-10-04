banned_words = ["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__"]

def jail(banned_words):
    command = str(input(">>>"))
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        exec(command)
    except Exception as e:
        print(f"Invalid command {e}")


while True:
    jail(banned_words)
