/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 10 - Ghost in the GATT
 *
 * The ESP32 exposes a service with 11 characteristics. All share the UUID
 * pattern:
 *   b100d000-C0XX-1000-8000-00805f9b34fb
 * where XX is a 1-byte value. 10 characteristics return decoy strings;
 * 1 returns the flag.
 *
 * Standard service/characteristic discovery reveals the service UUID but the
 * characteristic UUIDs look opaque. A hint in the advertising manufacturer
 * data reveals the high byte of XX is 0xC0.
 *
 * Participants must write a script that iterates XX from 0x00 to 0xFF,
 * directly reading each candidate UUID, to find the flag.
 *
 * Flag: WOCSA{uuid_brute_force_unlocks_secrets}
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>
#include <BLEAdvertising.h>

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

#define DEVICE_NAME  "THCON26_BLE_10"
#define FLAG         "WOCSA{uuid_brute_force_unlocks_secrets}"

// All characteristics share this UUID pattern:
//   b100d000-C0XX-1000-8000-00805f9b34fb
// The flag lives at XX = 0xDE (i.e., C0DE).
#define SERVICE_UUID   "b100d000-0000-1000-8000-00805f9b34fb"
#define FLAG_CHAR_UUID "b100d000-c0de-1000-8000-00805f9b34fb"

// 10 decoy UUIDs in the same C0XX space (excluding C0DE)
const char* decoyUUIDs[] = {
  "b100d000-c001-1000-8000-00805f9b34fb",
  "b100d000-c003-1000-8000-00805f9b34fb",
  "b100d000-c007-1000-8000-00805f9b34fb",
  "b100d000-c00f-1000-8000-00805f9b34fb",
  "b100d000-c01e-1000-8000-00805f9b34fb",
  "b100d000-c033-1000-8000-00805f9b34fb",
  "b100d000-c064-1000-8000-00805f9b34fb",
  "b100d000-c099-1000-8000-00805f9b34fb",
  "b100d000-c0ab-1000-8000-00805f9b34fb",
  "b100d000-c0ba-1000-8000-00805f9b34fb",
};

const char* decoyValues[] = {
  "not_here",
  "try_again",
  "keep_looking",
  "almost...",
  "nope",
  "wrong_uuid",
  "decoy_data",
  "access_denied",
  "invalid_handle",
  "null_value",
};

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 10"));
  display.println();
  display.println(F("Ghost in the"));
  display.println(F("GATT"));
  display.display();
}

void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 10 - Ghost in the GATT");
  Serial.println("[*] UUID pattern: b100d000-C0XX-1000-8000-00805f9b34fb");
  Serial.println("[*] Hint in manufacturer data: high byte = 0xC0");

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

  // Service holds 11 characteristics (10 decoys + 1 flag)
  // Each characteristic uses 3 handles (decl + value + padding), +1 for service decl → 34 minimum
  BLEService* pService = pServer->createService(BLEUUID(SERVICE_UUID), 40);

  // Register decoy characteristics
  for (int i = 0; i < 10; i++) {
    BLECharacteristic* c = pService->createCharacteristic(
      decoyUUIDs[i],
      BLECharacteristic::PROPERTY_READ
    );
    c->setValue(decoyValues[i]);
  }

  // Register the flag characteristic
  BLECharacteristic* flagChar = pService->createCharacteristic(
    FLAG_CHAR_UUID,
    BLECharacteristic::PROPERTY_READ
  );
  flagChar->setValue(FLAG);

  pService->start();

  // Advertising: include service UUID but embed the hint (0xC0) in manufacturer data
  // Manufacturer data format: 2-byte company ID (little-endian) + payload
  // Company ID 0xFFFF = test/unregistered; payload byte 0xC0 = hint
  BLEAdvertising* pAdv = BLEDevice::getAdvertising();
  pAdv->addServiceUUID(SERVICE_UUID);
  pAdv->setScanResponse(true);

  BLEAdvertisementData scanRespData;
  scanRespData.setName(DEVICE_NAME);  // restore name (overriding scan resp drops it)
  String mfData;
  mfData += (char)0xFF;  // company ID low byte (0xFFFF = test)
  mfData += (char)0xFF;  // company ID high byte
  mfData += (char)0xC0;  // hint: the variable byte range starts with 0xC0
  scanRespData.setManufacturerData(mfData);
  pAdv->setScanResponseData(scanRespData);

  BLEDevice::startAdvertising();

  Serial.println("[*] BLE advertising started - 11 characteristics, 1 contains the flag");
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
