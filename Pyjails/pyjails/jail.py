banned_words = ["exec", "eval", "import", "os"]

def jail(banned_words):
    command = input(">>> ")
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        exec(command)
    except Exception as e:
        print(f"Invalid command: {e}")

# Start the jail
while True:
    jail(banned_words)

