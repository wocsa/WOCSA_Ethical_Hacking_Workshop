# 1. Install the Arduino IDE
Go to the Arduino website then [download and install the Arduino IDE](https://www.arduino.cc/en/software).

This can be done by either downloading and running the Windows Installer, or by downloading the Windows ZIP file. If the Windows ZIP file is downloaded, it must simply be unzipped, and the folder extracted to a convenient location, such as the Windows Desktop. Just open the extracted folder and double-click the Arduino executable file to start Arduino.

# 2. Install the Digispark Board Support Package
## Open Preferences Dialog box
From the top menu of the Arduino IDE application, select File → Preferences to open the Preferences dialog box.

Paste the following in the Additional Boards Manager URLs: box of the Preferences dialog box.
https://raw.githubusercontent.com/ArminJo/DigistumpArduino/master/package_digistump_index.json

![arduino-preferences-urls](./InstallArduinoIDE/arduino-preferences-urls.png "arduino-preferences-urls")

## Open Boards Manager Dialog Box
In the Arduino IDE, use the top menu to navigate to Tools → Board → Boards Manager... to open the Boards Manager dialog box.

Type Digispark into the search field at the top of the Boards Manager dialog box that contains the text "Filter your search..." to easily find the Digispark package.

Find and install Digistump AVR Boards.

![digispark-board-package](InstallArduinoIDE/digispark-board-package.png "digispark-board-packages")

When installation completes, click the Close button at the bottom right of the dialog box.

# 3. Install the Digispark Windows 10 Drivers
Download the [Digispark Digistump Drivers for Windows](https://github.com/digistump/DigistumpArduino/releases/download/1.6.7/Digistump.Drivers.zip).

Unzip the Digistump.Drivers.zip file downloaded from the above link and extract the Digistump Drivers folder from it.

Double-click either DPinst64.exe on a 64-bit Windows computer, or DPinst.exe on a 32-bit Windows computer to install the Digispark drivers.

When prompted to install the driver with the following dialog box, click the Install button.

If a dialog box pops up that displays Windows can't verify the publisher of this driver software, click the Install this driver software anyway button.

After the driver installation has finished, click the Finish button in the Device Driver Installation dialog box.

# 4. Testing the Digispark Windows Installation
The board must not be plugged into a USB port, but must first be selected in the Arduino IDE. No port is selected. The sketch is uploaded, and when a prompt appears in the Arduino IDE, the board is plugged into a USB port.

## Digispark Blink Sketch
```
void setup() {                
  pinMode(0, OUTPUT);      // LED on Model B
  pinMode(1, OUTPUT);      // LED on Model A   
}

void loop() {
  digitalWrite(0, HIGH);   // Turn the LED on
  digitalWrite(1, HIGH);
  delay(1000);             // Wait for a second
  digitalWrite(0, LOW);    // Turn the LED off
  digitalWrite(1, LOW); 
  delay(1000);             // Wait for a second
}
```

## Select the Digispark Board in the Arduino IDE
From the top menu in the Arduino IDE, select Tools → Board → Digistump AVR Boards → Digispark (Default - 16.5MHz) to select the Digispark board.

## Load the Blink Sketch to the Digispark Board
Click the Arduino Upload button on the top toolbar before plugging the Digispark board into a USB port.

![digispark-start-upload](InstallArduinoIDE/digispark-start-upload.png "digispark-start-upload")

When the prompt Plug in device now... (will timeout in 60 seconds) appears, plug the Digispark board into a USB port of the computer.

After the sketch finishes uploading, a success message running: 100% complete and >> Micronucleus done. Thank you!
