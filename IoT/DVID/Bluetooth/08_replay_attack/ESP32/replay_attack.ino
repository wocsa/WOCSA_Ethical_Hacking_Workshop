/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 08 - Replay Attack
 *
 * The ESP32 requires a specific 4-byte magic payload to unlock the flag.
 * The magic bytes are: 0xDE 0xAD 0xBE 0xEF
 *
 * A PCAP file (capture.pcap) is provided alongside this challenge.
 * It contains a recording of a legitimate admin unlocking the device.
 * Participants must:
 *   1. Open the PCAP in Wireshark
 *   2. Filter for btatt WRITE operations
 *   3. Extract the magic payload (DEADBEEF)
 *   4. Replay it to unlock the flag characteristic
 *
 * Flag: WOCSA{replay_attacks_bypass_auth}
 *
 * Note: The PCAP file must be generated separately using a BLE sniffer
 * (e.g., Ubertooth One, nRF Sniffer, or Wireshark with a compatible adapter).
 * The capture should show: WRITE to AUTH_CHAR with value DE AD BE EF
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>

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

#define DEVICE_NAME   "THCON26_BLE_08"
#define FLAG          "WOCSA{replay_attacks_bypass_auth}"
#define LOCKED_MSG    "Locked: Authentication Required"

// Magic bytes required to unlock
static const uint8_t MAGIC[] = {0xDE, 0xAD, 0xBE, 0xEF};

#define SERVICE_UUID   "facade00-0000-1000-8000-00805f9b34fb"
#define FLAG_CHAR_UUID "facade00-0001-1000-8000-00805f9b34fb"
#define AUTH_CHAR_UUID "facade00-0002-1000-8000-00805f9b34fb"

BLECharacteristic* pFlagChar = nullptr;
bool unlocked = false;

class AuthCallback : public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic* pChar) {
    String val = pChar->getValue();

    if (val.length() == 4 &&
        (uint8_t)val[0] == MAGIC[0] &&
        (uint8_t)val[1] == MAGIC[1] &&
        (uint8_t)val[2] == MAGIC[2] &&
        (uint8_t)val[3] == MAGIC[3]) {
      unlocked = true;
      pFlagChar->setValue(FLAG);
      Serial.println("[+] Magic bytes matched! Flag unlocked.");
    } else {
      Serial.print("[-] Wrong magic bytes: ");
      for (int i = 0; i < (int)val.length(); i++) {
        Serial.printf("%02X ", (uint8_t)val[i]);
      }
      Serial.println();
    }
  }
};

class ServerCallbacks : public BLEServerCallbacks {
  void onConnect(BLEServer* pServer) {
    Serial.println("[+] Client connected");
  }
  void onDisconnect(BLEServer* pServer) {
    Serial.println("[-] Client disconnected, resetting...");
    unlocked = false;
    pFlagChar->setValue(LOCKED_MSG);
    pServer->startAdvertising();
  }
};

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 08"));
  display.println();
    display.println(F("Replay Attack"));;
  display.display();
}

void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 08 - Replay Attack");
  Serial.println("[*] Magic bytes: DE AD BE EF");

  Wire.begin(I2C_SDA, I2C_SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  } else {
    displayOk = true;
    showChallengeName();
    lastSwitch = millis();
  }

  BLEDevice::init(DEVICE_NAME);

  BLEServer* pServer = BLEDevice::createServer();
  pServer->setCallbacks(new ServerCallbacks());

  BLEService* pService = pServer->createService(SERVICE_UUID);

  // FLAG characteristic (READ)
  pFlagChar = pService->createCharacteristic(
    FLAG_CHAR_UUID,
    BLECharacteristic::PROPERTY_READ
  );
  pFlagChar->setValue(LOCKED_MSG);

  // AUTH characteristic (WRITE) — accepts magic bytes
  BLECharacteristic* pAuthChar = pService->createCharacteristic(
    AUTH_CHAR_UUID,
    BLECharacteristic::PROPERTY_WRITE
  );
  pAuthChar->setCallbacks(new AuthCallback());

  pService->start();

  BLEAdvertising* pAdv = BLEDevice::getAdvertising();
  pAdv->addServiceUUID(SERVICE_UUID);
  pAdv->setScanResponse(true);
  BLEDevice::startAdvertising();

  Serial.println("[*] BLE advertising started");
}

void loop() {
  if (displayOk && millis() - lastSwitch >= 10000) {
    lastSwitch = millis();
    showingLogo = !showingLogo;
    if (showingLogo) showLogo();
    else showChallengeName();
  }
  delay(100);
}
