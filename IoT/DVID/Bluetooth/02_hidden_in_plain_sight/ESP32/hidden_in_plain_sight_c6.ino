/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 02 - Hidden in Plain Sight
 *
 * The ESP32 broadcasts a BLE advertisement containing the flag embedded
 * in the Manufacturer Specific Data field (company ID 0xFFFF).
 * No GATT server is running — participants must inspect raw adv packets.
 *
 * Flag: WOCSA{ble_adv_leaks_data}
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <BLEDevice.h>
#include <BLEUtils.h>
#include <BLEAdvertising.h>

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

#define DEVICE_NAME "THCON26_BLE_02"

// Flag bytes to embed in Manufacturer Specific Data
// "WOCSA{ble_adv_leaks_data}" as ASCII
static const uint8_t flagData[] = {
  0xFF, 0xFF,  // Company ID (0xFFFF = test/unregistered)
  // WOCSA{ble_adv_leaks_data}
  0x57, 0x4F, 0x43, 0x53, 0x41, 0x7B,  // WOCSA{
  0x62, 0x6C, 0x65, 0x5F, 0x61, 0x64,  // ble_ad
  0x76, 0x5F, 0x6C, 0x65, 0x61, 0x6B,  // v_leak
  0x73, 0x5F, 0x64, 0x61, 0x74, 0x61,  // s_data
  0x7D                                   // }
};

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 02"));
  display.println();
  display.println(F("Hidden in Plain"));
  display.println(F("Sight"));
  display.display();
}
void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 02 - Hidden in Plain Sight");

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

  BLEAdvertising* pAdvertising = BLEDevice::getAdvertising();

  // Build custom advertisement data
  // Main advertisement: only Manufacturer Specific Data (type 0xFF)
  // Name in adv + mfg data would exceed 31-byte BLE adv payload limit
  BLEAdvertisementData advData;
  String mfgData((char*)flagData, sizeof(flagData));
  advData.setManufacturerData(mfgData);

  // Device name goes in scan response (separate 31-byte packet)
  BLEAdvertisementData scanResponse;
  scanResponse.setName(DEVICE_NAME);

  pAdvertising->setAdvertisementData(advData);
  pAdvertising->setScanResponseData(scanResponse);
  pAdvertising->setScanResponse(true);
  pAdvertising->start();

  Serial.println("[*] BLE advertising started");
  Serial.println("[*] Flag is hidden in Manufacturer Specific Data");
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
