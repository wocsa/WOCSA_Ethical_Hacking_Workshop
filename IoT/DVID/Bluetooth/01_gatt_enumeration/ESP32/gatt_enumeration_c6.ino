/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 01 - GATT Enumeration
 *
 * The ESP32 hosts a simple GATT server with a single service and a single
 * READ characteristic containing the flag in plain text.
 * Participants must scan, connect, enumerate, and read the characteristic.
 *
 * Flag: WOCSA{ble_gatt_is_easy_to_read}
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>
#include <BLE2902.h>

#define SCREEN_WIDTH   128
#define SCREEN_HEIGHT  64
#define OLED_RESET     -1
#define SCREEN_ADDRESS 0x3C
#define I2C_SDA        11
#define I2C_SCL        10

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

#include "../../wocsa_logo.h"

// Device & flag
#define DEVICE_NAME   "THCON26_BLE_01"
#define FLAG          "WOCSA{ble_gatt_is_easy_to_read}"

// Custom 128-bit UUIDs
#define SERVICE_UUID        "12345678-1234-1234-1234-123456789001"
#define CHARACTERISTIC_UUID "12345678-1234-1234-1234-123456789002"

BLEServer* pServer = nullptr;
BLECharacteristic* pCharacteristic = nullptr;
bool deviceConnected = false;

bool displayOk = false;
unsigned long lastSwitch = 0;
bool showingLogo = false;

void showLogo() {
  display.clearDisplay();
  display.drawBitmap(0, 0, epd_bitmap_wocsa_logo, 128, 64, SSD1306_WHITE);
  display.display();
}

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 01"));
  display.println();
  display.println(F("GATT Enumeration"));
  display.display();
}

class ServerCallbacks : public BLEServerCallbacks {
  void onConnect(BLEServer* pServer) {
    deviceConnected = true;
    Serial.println("[+] Client connected");
  }
  void onDisconnect(BLEServer* pServer) {
    deviceConnected = false;
    Serial.println("[-] Client disconnected, restarting advertising...");
    pServer->startAdvertising();
  }
};
void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 01 - GATT Enumeration");

  // Initialize OLED display
  Wire.setPins(I2C_SDA, I2C_SCL);
  Wire.begin();
  delay(100);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  } else {
    displayOk = true;
    showChallengeName();
    lastSwitch = millis();
  }

  BLEDevice::init(DEVICE_NAME);

  pServer = BLEDevice::createServer();
  pServer->setCallbacks(new ServerCallbacks());

  // Create service
  BLEService* pService = pServer->createService(SERVICE_UUID);

  // Create READ characteristic with the flag
  pCharacteristic = pService->createCharacteristic(
    CHARACTERISTIC_UUID,
    BLECharacteristic::PROPERTY_READ
  );
  pCharacteristic->setValue(FLAG);

  pService->start();

  // Start advertising
  BLEAdvertising* pAdvertising = BLEDevice::getAdvertising();
  pAdvertising->addServiceUUID(SERVICE_UUID);
  pAdvertising->setScanResponse(true);
  pAdvertising->setMinPreferred(0x06);
  BLEDevice::startAdvertising();

  Serial.println("[*] BLE advertising started");
  Serial.print("[*] Device name: ");
  Serial.println(DEVICE_NAME);
}

void loop() {
  if (displayOk && millis() - lastSwitch >= 10000) {
    lastSwitch = millis();
    showingLogo = !showingLogo;
    if (showingLogo) {
      showLogo();
    } else {
      showChallengeName();
    }
  }
  delay(100);
}
