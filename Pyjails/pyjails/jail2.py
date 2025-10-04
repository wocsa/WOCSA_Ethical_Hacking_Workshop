def jail():
    banned_words = ["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__"]
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
