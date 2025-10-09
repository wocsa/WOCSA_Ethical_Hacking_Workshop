# Warning
This workshop is for educational purposes only. Ethical hacking is conducted with the explicit permission of the system owner to improve security.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
  - [Introduction](#introduction)
    - [What is a jail?](#what-is-a-jail)
    - [Why Try to Solve a Jail?](#why-try-to-solve-a-jail)
    - [Basic Example of Jail](#basic-example-of-jail)
  - [Workshop](#workshop)
    - [Solve the Jails](#solve-the-jails)
    - [Write Your Own Jail](#write-your-own-jail)
    - [Try to Improve the Difficulty of Your Jail](#try-to-improve-the-difficulty-of-your-jail)
  - [Conclusion](#conclusion)
  - [References](#references)

## Introduction

### What is a jail?
Today, we’re diving into the concept of a "jail" in programming. A jail is an environment that limits what commands can be executed, mainly to enhance security. By restricting certain functionalities, we can protect applications from malicious activities.

### Why Try to Solve a Jail?
Understanding how to escape from such jails gives you insight into potential vulnerabilities within systems. It’s not just about finding a way out; it's about learning how to secure applications better and understanding the ethical implications of your skill set.

### Basic Example of Jail
To get you started, let’s look at a simplified example of a Python jail. This will help you grasp the concept quickly before moving on to more challenging tasks.

```python
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
```

In this example, you can attempt to execute a command like `print("Hello, World!")`. The first challenge is to figure out how to display your current username using `whoami`, but within the rules set by this jail.

## Workshop

### Solve the Jails
Now it’s your turn! In this session, you will work either individually or in pairs to solve a series of jails. The goal is to execute the command `whoami`, but you’ll have to be creative in your approach. Keep in mind the restrictions put in place!

### Write Your Own Jail
Next, you can create your own jail! Feel free to use Python or any other programming language you're comfortable with. Think about the restrictions you want to impose and how they might challenge others.

### Try to Improve the Difficulty of Your Jail
Once you’ve written your jail, consider how to make it more challenging. What additional measures can you take to prevent unauthorized commands? Discuss with your peers and share ideas for strengthening your jails.

## Conclusion
Remember that understanding these concepts is crucial in developing secure applications and acting responsibly in the tech community.

## References
- [Python Documentation](https://docs.python.org/)  
- [Secure Coding Practices](https://owasp.org/www-project-top-ten/)