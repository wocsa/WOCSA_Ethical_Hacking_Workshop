/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 06 - Role Reversal
 *
 * The ESP32 acts as a BLE Central device.
 * It continuously scans for a device advertising as "PwnMe_Beacon".
 * When found, it connects and writes the flag to a well-known characteristic.
 * It also prints the flag to Serial for confirmation.
 *
 * Participants must broadcast "PwnMe_Beacon" from their computer/phone
 * and expose a WRITE characteristic at the known UUID.
 *
 * Flag: WOCSA{you_are_the_peripheral_now}
 *
 * Target characteristic UUID (on participant device):
 *   deadbeef-0000-1000-8000-00805f9b34fb
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <BLEDevice.h>
#include <BLEScan.h>
#include <BLEClient.h>
#include <BLEAdvertisedDevice.h>

#define SCREEN_WIDTH   128
#define SCREEN_HEIGHT  64
#define OLED_RESET     -1
#define SCREEN_ADDRESS 0x3C
#define I2C_SDA        15
#define I2C_SCL        4

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

#include "../../wocsa_logo.h"

bool displayOk = false;
unsigned long lastSwitch = 0;
bool showingLogo = false;

void showLogo() {
  display.clearDisplay();
  display.drawBitmap(0, 0, epd_bitmap_wocsa_logo, 128, 64, SSD1306_WHITE);
  display.display();
}

#define TARGET_NAME   "PwnMe_Beacon"
#define FLAG          "WOCSA{you_are_the_peripheral_now}"
#define FLAG_CHAR_UUID "deadbeef-0000-1000-8000-00805f9b34fb"

BLEScan* pBLEScan = nullptr;
bool flagDelivered = false;
bool targetFound   = false;
BLEAdvertisedDevice* pTargetDevice = nullptr;

class AdvertisedDeviceCallbacks : public BLEAdvertisedDeviceCallbacks {
  void onResult(BLEAdvertisedDevice advertisedDevice) {
    Serial.print("[*] Found device: ");
    Serial.println(advertisedDevice.getName().c_str());

    if (advertisedDevice.getName() == TARGET_NAME) {
      Serial.println("[+] PwnMe_Beacon found! Will connect from loop...");
      pTargetDevice = new BLEAdvertisedDevice(advertisedDevice);
      targetFound = true;
      pBLEScan->stop();
    }
  }
};

void connectAndDeliver() {
  Serial.println("[+] Connecting to PwnMe_Beacon...");

  BLEClient* pClient = BLEDevice::createClient();
  if (pClient->connect(pTargetDevice)) {
    Serial.println("[+] Connected to PwnMe_Beacon!");

    BLERemoteCharacteristic* pChar = nullptr;

    // Iterate services to find our target characteristic
    std::map<std::string, BLERemoteService*>* services = pClient->getServices();
    for (auto& svcPair : *services) {
      BLERemoteService* svc = svcPair.second;
      std::map<std::string, BLERemoteCharacteristic*>* chars = svc->getCharacteristics();
      for (auto& charPair : *chars) {
        if (charPair.first == FLAG_CHAR_UUID) {
          pChar = charPair.second;
          break;
        }
      }
      if (pChar) break;
    }

    if (pChar && pChar->canWrite()) {
      pChar->writeValue(FLAG, strlen(FLAG));
      Serial.println("[+] Flag written to participant device characteristic!");
    } else {
      Serial.println("[!] Target characteristic not found or not writable.");
      Serial.println("[+] Flag delivery via Serial:");
    }

    // Always print the flag to Serial as a fallback
    Serial.print("[FLAG] ");
    Serial.println(FLAG);
    flagDelivered = true;

    pClient->disconnect();
  } else {
    Serial.println("[-] Connection failed.");
  }

  delete pTargetDevice;
  pTargetDevice = nullptr;
  targetFound = false;
}

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 06"));
  display.println();
    display.println(F("Role Reversal"));;
  display.display();
}

void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 06 - Role Reversal");
  Serial.println("[*] ESP32 is scanning for 'PwnMe_Beacon'...");

  Wire.begin(I2C_SDA, I2C_SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  } else {
    displayOk = true;
    showChallengeName();
    lastSwitch = millis();
  }

  BLEDevice::init("THCON26_Scanner");

  pBLEScan = BLEDevice::getScan();
  pBLEScan->setAdvertisedDeviceCallbacks(new AdvertisedDeviceCallbacks());
  pBLEScan->setActiveScan(true);
  pBLEScan->setInterval(100);
  pBLEScan->setWindow(99);
  pBLEScan->start(5, false);
}

void loop() {
  if (displayOk && millis() - lastSwitch >= 10000) {
    lastSwitch = millis();
    showingLogo = !showingLogo;
    if (showingLogo) showLogo();
    else showChallengeName();
  }

  if (targetFound) {
    connectAndDeliver();
    delay(2000);
    pBLEScan->start(5, false);
    return;
  }

  if (!flagDelivered) {
    if (!pBLEScan->isScanning()) {
      delay(500);
      pBLEScan->start(5, false);
    }
    delay(100);
  } else {
    delay(5000);
    Serial.println("[*] Waiting... Broadcast 'PwnMe_Beacon' to receive the flag again.");
    flagDelivered = false;
    pBLEScan->start(5, false);
  }
}
