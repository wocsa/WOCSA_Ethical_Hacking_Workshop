# Warning
This workshop is for educational purposes only.
Ethical hacking is conducted with the explicit permission of the system owner to improve security.

# Table of Contents

- [Warning](#warning)
- [Table of Contents](#table-of-contents)
- [Introduction](#introduction)
  - [What is a Bad USB?](#what-is-a-bad-usb)
  - [How bad USB works?](#how-bad-usb-works)
  - [Why is it dangerous?](#why-is-it-dangerous)
  - [How to protect yourself?](#how-to-protect-yourself)
- [Workshop](#workshop)
  - [Setup](#setup)
    - [Requirements](#requirements)
    - [Installation of the Arduino IDE](#installation-of-the-arduino-ide)
    - [Presentation of basic commands](#presentation-of-basic-commands)
      - [Basic functions](#basic-functions)
      - [Standard Keys](#standard-keys)
      - [Function Keys](#function-keys)
      - [Control Keys](#control-keys)
      - [Navigation Keys](#navigation-keys)
      - [Numeric Keypad Keys](#numeric-keypad-keys)
      - [Special Keys](#special-keys)
      - [Media Playback Controls](#media-playback-controls)
      - [Volume Controls](#volume-controls)
      - [Additional Media Functions](#additional-media-functions)
      - [Web and Application Controls](#web-and-application-controls)
  - [Playground](#playground)
    - [Challenge](#challenge)
    - [Solutions](#solutions)
  - [Defense](#defense)
- [Conclusion](#conclusion)

# Introduction

## What is a Bad USB?
A Bad USB is a type of USB device that is designed to exploit a computer's USB port. This can be used to steal data, install malware, or even take control of the computer. Bad USB attacks are a type of attack that uses a USB device to exploit a computer's USB port.

## How bad USB works?
The USB microcontroller chip that contains the firmware is used to identify the type of device that's connected and its capabilities. Once the firmware is compromised, it's just a matter of time until the hacker reverse engineers the USB device to insert the malicious code within the workstation. This exposes the organization to a whole range of security attacks, such as logic bombs, data theft, ransomware, and more.

## Why is it dangerous?
Bad USB attacks are dangerous because they can be used to steal data, install malware, or even take control of the computer. This can be done without the user's knowledge, and can be very difficult to detect.

Create a Bad USB could be cheap for a hacker and not very difficult. You can find affordable Bad USB, looking like really USB, on the internet. The hacker just needs to plug the Bad USB into the computer and wait for the attack to be executed. And because it is a physic attack, no one would be aware of it! One example you can find is in ["Mister Robot"](https://www.youtube.com/watch?v=7x0vl6ikO5M) series 😉.

## How to protect yourself?
To protect yourself from Bad USB attacks, you should be careful about what USB devices you connect to your computer. You should also keep your computer's software up to date, and use antivirus software to protect against malware. In enterprises, you can use a USB port blocker to prevent unauthorized USB devices from being connected to your computer or deactivate USB ports.

# Workshop

## Setup
### Requirements
Here, we will use Digispark arduino board to create a Bad USB. The Digispark is a microcontroller board that is based on the ATtiny85. It is similar to the Arduino, but it is much smaller and cheaper. The Digispark can be programmed using the Arduino IDE, and it can be used to create a Bad USB. You can find the Digispark on the internet for a few dollars.

### Installation of the Arduino IDE
You can find all the instructions for Windows installation [here](./Installation/InstallArduinoIDEWindows.md) and for Linux installation [here](./Installation/InstallArduinoIDELinux.md).

### Presentation of basic commands
The base code is:
``` C
void setup() {
  // put your setup code here, to run once:

}

void loop() {
  // put your main code here, to run repeatedly:

}
```
The `setup()` function is called when a sketch starts. Use it to initialize variables, pin modes, start using libraries, etc. The `loop()` function is called after `setup()` has finished. Use it to run code over and over again, forever.

#### Basic functions
1. `DigiKeyboard.delay(X);`: This command is used to delay the execution of the next command by X milliseconds. You can change the value to change the delay.
2. `DigiKeyboard.sendKeyStroke(key, modifier)`: Presses 1,2 or 3 keys together (with 3 keys they must me seperated with comma then pipe symbol, and 2 keys is just a comma). For example, `DigiKeyboard.sendKeyStroke(KEY_R, MOD_GUI_LEFT);` will press the Windows key and the R key together.
3. `DigiKeyboard.print(text);`: Prints a line of text (like typing) - USE brackets and quotes (""). For example, `DigiKeyboard.print("Hello World!");` will type "Hello World!".
4. `DigiKeyboard.println(text);`: Prints a line of text and then presses the enter key - USE brackets and quotes (""). For example, `DigiKeyboard.println("Hello World!");` will type "Hello World!" and then press the enter key.
5. `setModifier(modifier);`: Sets a modifier key (like CTRL, SHIFT, ALT) which will be held down.
6. `setKey(key);`: Sets a regular key to be pressed.
7. `sendKeyPress();`: Sends the current modifier and key values set by setModifier and setKey.

#### Standard Keys
- KEY_A, KEY_B, KEY_C, ..., KEY_Z: Representing the alphabet keys.
- KEY_1, KEY_2, ..., KEY_9, KEY_0: Representing the number keys.
- KEY_ENTER
- KEY_ESC
- KEY_BACKSPACE
- KEY_TAB
- KEY_SPACE
- KEY_MINUS: The ‘-‘ key.
- KEY_EQUAL: The ‘=’ key.
- KEY_LEFT_BRACE: The ‘[‘ key.
- KEY_RIGHT_BRACE: The ‘]’ key.
- KEY_BACKSLASH: The ‘\’ key.
- KEY_SEMICOLON: The ‘;’ key.
- KEY_QUOTE: The ‘”’ key.
- KEY_TILDE: The ‘`’ key.
- KEY_COMMA: The ‘,’ key.
- KEY_PERIOD: The ‘.’ key.
- KEY_SLASH: The ‘/’ key.
- KEY_CAPS_LOCK

#### Function Keys
- KEY_F1, KEY_F2, ..., KEY_F12: Representing the function keys.

#### Control Keys
- KEY_LEFT_CTRL
- KEY_LEFT_SHIFT
- KEY_LEFT_ALT
- KEY_LEFT_GUI: Often represents the Windows key or Command key on Mac.
- KEY_RIGHT_CTRL
- KEY_RIGHT_SHIFT
- KEY_RIGHT_ALT
- KEY_RIGHT_GUI

#### Navigation Keys
- KEY_UP_ARROW
- KEY_DOWN_ARROW
- KEY_LEFT_ARROW
- KEY_RIGHT_ARROW
- KEY_HOME
- KEY_END
- KEY_PAGE_UP
- KEY_PAGE_DOWN
- KEY_INSERT
- KEY_DELETE

#### Numeric Keypad Keys
- KEYPAD_0, KEYPAD_1, ..., KEYPAD_9
- KEYPAD_PERIOD
- KEYPAD_DIVIDE
- KEYPAD_MULTIPLY
- KEYPAD_MINUS
- KEYPAD_PLUS
- KEYPAD_ENTER

#### Special Keys
- KEY_PRINTSCREEN
- KEY_SCROLLLOCK
- KEY_PAUSE
- KEY_NUM_LOCK

#### Media Playback Controls
- MEDIA_PLAY_PAUSE: Toggles play/pause of the media player.
- MEDIA_STOP_CD: Stops playback.
- MEDIA_PREVIOUS_TRACK: Goes to the previous track.
- MEDIA_NEXT_TRACK: Goes to the next track.
- MEDIA_REWIND: Rewinds the current track.
- MEDIA_FAST_FORWARD: Fast forwards the current track.

#### Volume Controls
- MEDIA_VOLUME_UP: Increases the system volume.
- MEDIA_VOLUME_DOWN: Decreases the system volume.
- MEDIA_VOLUME_MUTE: Mutes/unmutes the system volume.

#### Additional Media Functions
- MEDIA_EJECT_CD: Ejects the CD or DVD (if applicable).
- MEDIA_PLAY: Starts playback (may be different from play/pause toggle).
- MEDIA_PAUSE: Pauses playback (may be different from play/pause toggle).

#### Web and Application Controls
- MEDIA_WWW: Opens the default web browser.
- MEDIA_CALCULATOR: Opens the calculator application.
- MEDIA_EMAIL: Opens the default email application.
- MEDIA_BROWSER_SEARCH: Activates the browser’s search function.
- MEDIA_BROWSER_HOME: Goes to the browser’s home page.
- MEDIA_BROWSER_BACK: Goes back in the browser.
- MEDIA_BROWSER_FORWARD: Goes forward in the browser.

## Playground
### Challenge
You will have access to two computers, one on Linux and one on Windows. The two computers are on a NATed network. You will have only 15 seconds to plug the Bad USB into the computer and execute the attack. The goal is to get the flag on the computer. Good luck!

### Solutions
You can find the solutions for the challenge [here](./Solutions/Solutions.md).

## Defense
Lock you screen ! It is the first defense against Bad USB. If you leave your computer unattended, someone could plug a Bad USB into your computer and execute an attack. If you lock your screen, the attacker won't be able to access your computer.

Then, be careful about what USB devices you connect to your computer. If you find a USB device that you don't recognize, don't connect it to your computer. It could be a Bad USB.

Finally, keep your computer's software up to date, and use antivirus software to protect against malware. This will help protect your computer from Bad USB attacks.

# Conclusion
