/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 09 - Buffer Overflow (Simulated)
 *
 * The ESP32 exposes:
 *   - INPUT_CHAR (WRITE): accepts input; normal payloads (<= 20 bytes) are echoed
 *     back to OUTPUT_CHAR. Payloads > 50 bytes trigger a "crash" state.
 *   - OUTPUT_CHAR (READ): normally shows the echoed input or an error message.
 *     In "crash" state, it returns the flag.
 *
 * This simulates a buffer overflow where oversized input corrupts memory
 * and causes unintended data to be exposed.
 *
 * Participants must:
 *   1. Read OUTPUT_CHAR (normal state)
 *   2. Write a short payload to INPUT_CHAR → see echo in OUTPUT_CHAR
 *   3. Fuzz INPUT_CHAR with increasing payload sizes
 *   4. When payload > 50 bytes, OUTPUT_CHAR changes to the flag
 *
 * Flag: WOCSA{fuzzing_reveals_hidden_bugs}
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

#define DEVICE_NAME   "THCON26_BLE_09"
#define FLAG          "WOCSA{fuzzing_reveals_hidden_bugs}"
#define IDLE_MSG      "Ready: send input"
#define OVERFLOW_SIZE 50  // Trigger threshold

#define SERVICE_UUID     "0verfl0w-0000-1000-8000-00805f9b34fb"
#define INPUT_CHAR_UUID  "0verfl0w-0001-1000-8000-00805f9b34fb"
#define OUTPUT_CHAR_UUID "0verfl0w-0002-1000-8000-00805f9b34fb"

BLECharacteristic* pOutputChar = nullptr;
bool crashed = false;

class InputCallback : public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic* pChar) {
    String val = pChar->getValue();
    size_t len = val.length();

    Serial.print("[*] Received input of length: ");
    Serial.println(len);

    if (len > OVERFLOW_SIZE) {
      // Simulate crash / buffer overflow
      crashed = true;
      pOutputChar->setValue(FLAG);
      Serial.println("[!!!] OVERFLOW TRIGGERED - flag exposed!");
    } else if (len == 0) {
      pOutputChar->setValue(IDLE_MSG);
    } else if (len <= 20) {
      // Normal: echo the input
      pOutputChar->setValue(val.c_str());
      Serial.print("[*] Echoing: ");
      Serial.println(val);
    } else {
      // Between 21 and 50: warn but don't crash
      pOutputChar->setValue("Warning: input truncated at 20 bytes");
      Serial.println("[!] Oversized input (21-50 bytes), truncated.");
    }
  }
};

class ServerCallbacks : public BLEServerCallbacks {
  void onConnect(BLEServer* pServer) {
    Serial.println("[+] Client connected");
  }
  void onDisconnect(BLEServer* pServer) {
    Serial.println("[-] Client disconnected, resetting state...");
    crashed = false;
    pOutputChar->setValue(IDLE_MSG);
    pServer->startAdvertising();
  }
};

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 09"));
  display.println();
    display.println(F("Buffer Overflow"));;
  display.display();
}

void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 09 - Buffer Overflow (Simulated)");
  Serial.printf("[*] Overflow threshold: > %d bytes\n", OVERFLOW_SIZE);

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

  // Note: UUID "0verfl0w-..." uses '0' (zero) not 'O' (letter O)
  BLEService* pService = pServer->createService(BLEUUID("30766572-666c-3077-0000-00805f9b34fb"), 12);

  // OUTPUT characteristic (READ)
  pOutputChar = pService->createCharacteristic(
    "30766572-666c-3077-0002-00805f9b34fb",
    BLECharacteristic::PROPERTY_READ
  );
  pOutputChar->setValue(IDLE_MSG);

  // INPUT characteristic (WRITE)
  BLECharacteristic* pInputChar = pService->createCharacteristic(
    "30766572-666c-3077-0001-00805f9b34fb",
    BLECharacteristic::PROPERTY_WRITE
  );
  pInputChar->setCallbacks(new InputCallback());

  pService->start();

  BLEAdvertising* pAdv = BLEDevice::getAdvertising();
  pAdv->addServiceUUID("30766572-666c-3077-0000-00805f9b34fb");
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
