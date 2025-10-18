banned_words = ["exec", "eval", "builtins", "getattr", "globals", "locals", "import", "os", "open", "__", "breakpoint"]

while True:
    command = input(">>>")
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
    else:
        try:
            exec(command)
        except Exception as e:
            print(f"Invalid command, {e}")
