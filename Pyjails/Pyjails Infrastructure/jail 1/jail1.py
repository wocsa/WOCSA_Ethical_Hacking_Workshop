banned_words = ["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__", "subprocess", "breakpoint"]

def jail(banned_words):
    try:
        command = str(input(">>>"))
    except:
        import sys
        sys.exit(0)
    if any(bad in command for bad in banned_words):
        print("Unauthorized command.")
        return
    try:
        exec(command)
    except Exception as e:
        print(f"Invalid command {e}")


while True:
    import judge
    if judge.if_who_am_i_detected():
        import flagger
        print(flagger.get_flag("jail 1"))
        del flagger
    del judge
    jail(banned_words)
