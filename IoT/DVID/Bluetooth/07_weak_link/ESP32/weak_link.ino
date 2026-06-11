/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 07 - Weak Link
 *
 * The ESP32 requires BLE legacy pairing with a 4-digit PIN (1234).
 * The flag characteristic is only accessible after successful pairing.
 * Unpaired clients receive "Pairing Required".
 *
 * Participants must brute-force the 4-digit PIN (0000-9999).
 *
 * Flag: WOCSA{pin_0042_is_not_a_password}
 */

#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>
#include <BLESecurity.h>

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

#define DEVICE_NAME  "THCON26_BLE_07"
#define FLAG         "WOCSA{pin_0042_is_not_a_password}"
#define PAIRING_MSG  "Pairing Required"

// PIN code (4 digits)
#define PIN_CODE 42

#define SERVICE_UUID   "b1eb1eb1-0000-1000-8000-00805f9b34fb"
#define FLAG_CHAR_UUID "b1eb1eb1-0001-1000-8000-00805f9b34fb"

BLECharacteristic* pFlagChar = nullptr;
BLEServer* pServer = nullptr;

class SecurityCallbacks : public BLESecurityCallbacks {
  uint32_t onPassKeyRequest() {
    // This is called when the ESP32 needs to provide or confirm a passkey
    return PIN_CODE;
  }

  void onPassKeyNotify(uint32_t pass_key) {
    Serial.print("[*] Passkey Notify: ");
    Serial.println(pass_key);
  }

  bool onConfirmPIN(uint32_t pin) {
    Serial.print("[*] Confirm PIN: ");
    Serial.println(pin);
    return (pin == PIN_CODE);
  }

  bool onSecurityRequest() {
    return true;
  }

  void onAuthenticationComplete(esp_ble_auth_cmpl_t cmpl) {
    if (cmpl.success) {
      Serial.println("[+] Pairing successful! Unlocking flag...");
      pFlagChar->setValue(FLAG);
    } else {
      Serial.println("[-] Pairing failed.");
      pFlagChar->setValue(PAIRING_MSG);
    }
  }
};

class ServerCallbacks : public BLEServerCallbacks {
  void onConnect(BLEServer* pServer) {
    Serial.println("[+] Client connected, waiting for pairing...");
  }
  void onDisconnect(BLEServer* pServer) {
    Serial.println("[-] Client disconnected, resetting...");
    pFlagChar->setValue(PAIRING_MSG);
    pServer->startAdvertising();
  }
};

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 07"));
  display.println();
    display.println(F("Weak Link"));;
  display.display();
}

void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 07 - Weak Link");
  Serial.print("[*] PIN: ");
  Serial.println(PIN_CODE);  // In a real challenge, remove this line

  Wire.begin(I2C_SDA, I2C_SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  } else {
    displayOk = true;
    showChallengeName();
    lastSwitch = millis();
  }

  BLEDevice::init(DEVICE_NAME);

  // Configure security: legacy pairing with passkey
  BLEDevice::setSecurityCallbacks(new SecurityCallbacks());

  BLESecurity* pSecurity = new BLESecurity();
  pSecurity->setAuthenticationMode(ESP_LE_AUTH_REQ_SC_MITM_BOND);
  pSecurity->setCapability(ESP_IO_CAP_OUT);  // ESP32 outputs passkey
  pSecurity->setInitEncryptionKey(ESP_BLE_ENC_KEY_MASK | ESP_BLE_ID_KEY_MASK);

  pServer = BLEDevice::createServer();
  pServer->setCallbacks(new ServerCallbacks());

  BLEService* pService = pServer->createService(SERVICE_UUID);

  pFlagChar = pService->createCharacteristic(
    FLAG_CHAR_UUID,
    BLECharacteristic::PROPERTY_READ
  );
  pFlagChar->setAccessPermissions(ESP_GATT_PERM_READ_ENCRYPTED);
  pFlagChar->setValue(PAIRING_MSG);

  pService->start();

  BLEAdvertising* pAdv = BLEDevice::getAdvertising();
  pAdv->addServiceUUID(SERVICE_UUID);
  pAdv->setScanResponse(true);
  BLEDevice::startAdvertising();

  Serial.println("[*] BLE advertising started with pairing required");
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
