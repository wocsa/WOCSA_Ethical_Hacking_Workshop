def jail():
    banned_words= []
    #banned_words = ["exec", "eval", "builtins", ".", "getattr", "globals", "locals", "import", "os", "open", "__", "breakpoint"]
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
    
#["exe"+"c(_"+"_im"+"port_"+"_('o"+"s')"+f"{chr(46)}system('whoami'))"][0]
