/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 04 - Identity Theft
 *
 * The ESP32 only allows connections from a specific trusted MAC address.
 * The trusted MAC is hinted in the scan response (device name suffix).
 * Any other MAC is immediately disconnected.
 *
 * Participants must:
 *   1. Scan and find the trusted MAC hint in the advertisement
 *   2. Spoof their adapter's MAC to match DE:AD:BE:EF:CA:FE
 *   3. Reconnect and read the flag
 *
 * Flag: WOCSA{mac_auth_is_not_real_security}
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
#define I2C_SDA        11
#define I2C_SCL        10

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

#define DEVICE_NAME      "THCON26_BLE_04"
#define SCAN_RESP_NAME   "Trusted:DE:AD:BE:EF:CA:FE"
#define FLAG             "WOCSA{mac_auth_is_not_real_security}"
#define ACCESS_DENIED    "Access Denied: Unknown device"

// Trusted MAC address (lowercase, colon-separated as returned by ESP32 API)
#define TRUSTED_MAC      "de:ad:be:ef:ca:fe"

#define SERVICE_UUID     "cafebabe-0000-1000-8000-00805f9b34fb"
#define FLAG_CHAR_UUID   "cafebabe-0001-1000-8000-00805f9b34fb"

BLECharacteristic* pFlagChar = nullptr;
BLEServer* pServer = nullptr;

class ServerCallbacks : public BLEServerCallbacks {
  void onConnect(BLEServer* pServer, ble_gap_conn_desc* desc) {
    // Get connecting device MAC
    String mac = BLEAddress(desc->peer_ota_addr).toString();

    Serial.print("[*] Connection attempt from MAC: ");
    Serial.println(mac);

    if (mac == String(TRUSTED_MAC)) {
      Serial.println("[+] Trusted MAC! Access granted.");
      pFlagChar->setValue(FLAG);
    } else {
      Serial.println("[-] Untrusted MAC! Disconnecting...");
      pFlagChar->setValue(ACCESS_DENIED);
      // Disconnect after a short delay
      delay(100);
      pServer->disconnect(desc->conn_handle);
    }
  }

  void onDisconnect(BLEServer* pServer) {
    Serial.println("[-] Client disconnected, restarting advertising...");
    pFlagChar->setValue(ACCESS_DENIED);
    pServer->startAdvertising();
  }
};

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 04"));
  display.println();
  display.println(F("Identity Theft"));
  display.display();
}
void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 04 - Identity Theft");
  Serial.print("[*] Trusted MAC: ");
  Serial.println(TRUSTED_MAC);

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

  BLEService* pService = pServer->createService(SERVICE_UUID);

  pFlagChar = pService->createCharacteristic(
    FLAG_CHAR_UUID,
    BLECharacteristic::PROPERTY_READ
  );
  pFlagChar->setValue(ACCESS_DENIED);

  pService->start();

  // Main advertisement: explicit device name
  // Note: service UUID is intentionally excluded from ADV_IND — the 128-bit UUID
  // consumes 18 bytes, leaving no room for the 16-byte device name in the 31-byte
  // ADV_IND payload. Without an explicit name here, BlueZ only sees the scan
  // response name and displays "Trusted:DE:AD:BE:EF:CA:FE" as the device name.
  BLEAdvertising* pAdvertising = BLEDevice::getAdvertising();
  BLEAdvertisementData advData;
  advData.setFlags(0x06);  // LE General Discoverable | BR/EDR Not Supported (required for discoverability)
  advData.setName(DEVICE_NAME);
  pAdvertising->setAdvertisementData(advData);

  // Scan response: hint about the trusted MAC
  BLEAdvertisementData scanRsp;
  scanRsp.setName(SCAN_RESP_NAME);
  pAdvertising->setScanResponseData(scanRsp);

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
