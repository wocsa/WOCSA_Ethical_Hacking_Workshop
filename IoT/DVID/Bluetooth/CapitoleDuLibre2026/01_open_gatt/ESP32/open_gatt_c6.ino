/*
 * CAPITOLE DU LIBRE 2026 - DVID/WOCSA Bluetooth Challenge
 * Challenge 01 - Open GATT
 *
 * A beginner GATT enumeration challenge. The badge exposes four services:
 *   - the standard Device Information service (0x180A)
 *   - "libre"   : README / LICENSE / AUTHORS, the one service that is advertised
 *   - "source"  : not advertised - only a full service discovery reveals it.
 *                 It holds the flag in plain text.
 *   - "checkin" : write the flag back, get the answer on a notification and a
 *                 success animation on the OLED. Show it at the WOCSA stand.
 *
 * Flag: WOCSA{free_as_in_freedom_open_as_in_gatt}
 *
 * Board: ESP32-C6 (NimBLE BLE stack). NimBLE creates the 0x2902 CCCD for
 * NOTIFY characteristics by itself, so there is no BLE2902.h here.
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
#define I2C_SDA        6
#define I2C_SCL        7

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire);

#include "../../../wocsa_logo.h"
#include "../../../capitole_logo.h"

// Running several badges side by side at the stand? Bump the suffix per unit
// (CDL26_BLE_02, _03, ...) so participants can tell them apart. This name is
// both advertised and shown on the title screen.
#define DEVICE_NAME   "CDL26_BLE_01"
#define FLAG          "WOCSA{free_as_in_freedom_open_as_in_gatt}"
#define CHECKIN_TOKEN "CDL26_CHECKIN_OK"

// ---------------------------------------------------------------------------
// GATT layout
// ---------------------------------------------------------------------------
// Device Information (standard, 16-bit UUIDs)
#define DIS_SVC_UUID      ((uint16_t)0x180A)
#define DIS_MANUF_UUID    ((uint16_t)0x2A29)
#define DIS_MODEL_UUID    ((uint16_t)0x2A24)
#define DIS_FW_UUID       ((uint16_t)0x2A26)

// "libre" - advertised
#define LIBRE_SVC_UUID      "cd1b0001-0000-1000-8000-00805f9b34fb"
#define LIBRE_README_UUID   "cd1b0001-0001-1000-8000-00805f9b34fb"
#define LIBRE_LICENSE_UUID  "cd1b0001-0002-1000-8000-00805f9b34fb"
#define LIBRE_AUTHORS_UUID  "cd1b0001-0003-1000-8000-00805f9b34fb"

// "source" - NOT advertised, discovery only
#define SOURCE_SVC_UUID     "cd1b0002-0000-1000-8000-00805f9b34fb"
#define SOURCE_MAIN_UUID    "cd1b0002-0001-1000-8000-00805f9b34fb"
#define SOURCE_FLAG_UUID    "cd1b0002-0002-1000-8000-00805f9b34fb"

// "checkin"
#define CHECKIN_SVC_UUID    "cd1b00ff-0000-1000-8000-00805f9b34fb"
#define CHECKIN_INFO_UUID   "cd1b00ff-00f0-1000-8000-00805f9b34fb"  // read
#define CHECKIN_SUBMIT_UUID "cd1b00ff-00f1-1000-8000-00805f9b34fb"  // write
#define CHECKIN_STATUS_UUID "cd1b00ff-00f2-1000-8000-00805f9b34fb"  // read + notify

#define README_TEXT   "Free software has nothing to hide. Neither does this badge: enumerate ALL services."
#define LICENSE_TEXT  "GPL-3.0-or-later"
#define AUTHORS_TEXT  "WOCSA, DVID - for Capitole du Libre 2026"
#define MAIN_TEXT     "int main(void) { return read_every_characteristic(); }"
#define CHECKIN_TEXT  "Write the flag here to check in."
#define STATUS_WAITING "WAITING"
#define STATUS_DENIED  "DENIED"
#define STATUS_GRANTED "GRANTED:" CHECKIN_TOKEN

BLEServer*         pServer     = nullptr;
BLECharacteristic* pStatusChar = nullptr;

// Set by the BLE callbacks, consumed by loop(). Callbacks stay short so the
// BLE stack is never blocked by the OLED animation.
volatile bool pendingAccept = false;
volatile bool pendingReject = false;

// ---------------------------------------------------------------------------
// Display
// ---------------------------------------------------------------------------
bool displayOk = false;

enum DisplayMode { MODE_IDLE, MODE_DENIED, MODE_SUCCESS };
DisplayMode mode = MODE_IDLE;

#define IDLE_SCREEN_MS 6000
#define DENIED_MS      1500
#define FRAME_MS       50

uint8_t       idleScreen = 0;
unsigned long lastSwitch = 0;
unsigned long lastFrame  = 0;
unsigned long modeStart  = 0;

void showTitle() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("CAPITOLE DU LIBRE 26"));
  display.println(F("Open GATT"));
  display.println();
  display.println(F("Everything is open."));
  display.println(F("Enumerate, read,"));
  display.println(F("check in the flag."));
  display.println();
  display.println(F(DEVICE_NAME));
  display.display();
}

void showWocsaLogo() {
  display.clearDisplay();
  display.drawBitmap(0, 0, epd_bitmap_wocsa_logo, 128, 64, SSD1306_WHITE);
  display.display();
}

void showCapitoleLogo() {
  display.clearDisplay();
  display.drawBitmap(0, 0, epd_bitmap_capitole_logo, 128, 64, SSD1306_WHITE);
  display.display();
}

void (*idleScreens[])() = { showTitle, showWocsaLogo, showCapitoleLogo };
const uint8_t IDLE_SCREEN_COUNT = sizeof(idleScreens) / sizeof(idleScreens[0]);

void showDenied() {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(2);
  display.setCursor(28, 16);
  display.print(F("DENIED"));
  display.setTextSize(1);
  display.setCursor(6, 44);
  display.print(F("that is not the flag"));
  display.drawRect(0, 0, 128, 64, SSD1306_WHITE);
  display.display();
}

// Success animation, driven entirely by elapsed time so it never blocks.
//   0.0-1.5s  shockwave of expanding rings
//   1.5-3.5s  blinking FLAG FOUND
//   3.5-6.0s  Capitole du Libre credits scrolling up
//   >6.0s     latched: Capitole logo and FLAG FOUND alternate every 2 s
void drawSuccessFrame(unsigned long elapsed) {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  if (elapsed < 1500) {
    int lead = (int)(elapsed / 18);              // ~83 px over 1.5 s
    for (int i = 0; i < 4; i++) {
      int r = lead - i * 20;
      if (r > 0 && r < 90) display.drawCircle(64, 32, r, SSD1306_WHITE);
    }
    display.setTextSize(1);
    display.setCursor(40, 29);
    display.print(F("CHECK-IN"));

  } else if (elapsed < 3500) {
    if (((elapsed - 1500) / 250) % 2 == 0) {
      display.setTextSize(2);
      display.setCursor(40, 12);
      display.print(F("FLAG"));
      display.setCursor(34, 32);
      display.print(F("FOUND"));
    }
    display.drawRect(0, 0, 128, 64, SSD1306_WHITE);
    display.drawRect(2, 2, 124, 60, SSD1306_WHITE);

  } else if (elapsed < 6000) {
    int y = 64 - (int)((elapsed - 3500) / 40);   // scroll up over 2.5 s
    display.setTextSize(1);
    display.setCursor(4, y);
    display.print(F("Capitole du Libre 26"));
    display.setCursor(10, y + 14);
    display.print(F("Toulouse - ENSEEIHT"));
    display.setCursor(16, y + 32);
    display.print(F("Show this screen"));
    display.setCursor(10, y + 42);
    display.print(F("at the WOCSA stand!"));

  } else if (((elapsed - 6000) / 2000) % 2 == 0) {
    display.drawBitmap(0, 0, epd_bitmap_capitole_logo, 128, 64, SSD1306_WHITE);

  } else {
    display.setTextSize(2);
    display.setCursor(40, 6);
    display.print(F("FLAG"));
    display.setCursor(28, 26);
    display.print(F("FOUND!"));
    display.setTextSize(1);
    display.setCursor(13, 50);
    display.print(F("-> WOCSA stand <-"));
    display.drawRect(0, 0, 128, 64, SSD1306_WHITE);
  }

  display.display();
}

// ---------------------------------------------------------------------------
// BLE callbacks
// ---------------------------------------------------------------------------
class ServerCallbacks : public BLEServerCallbacks {
  void onConnect(BLEServer*) {
    Serial.println("[+] Client connected");
  }
  void onDisconnect(BLEServer*) {
    Serial.println("[-] Client disconnected, advertising again");
    BLEDevice::startAdvertising();
  }
};

class SubmitCallback : public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic* pChar) {
    String value = pChar->getValue();
    value.trim();
    if (value == FLAG) {
      Serial.println("[+] Correct flag submitted - check-in accepted.");
      pendingAccept = true;
    } else {
      Serial.printf("[-] Rejected submission: %s\n", value.c_str());
      pendingReject = true;
    }
  }
};

static void addReadChar(BLEService* svc, BLEUUID uuid, const char* value) {
  BLECharacteristic* ch = svc->createCharacteristic(uuid, BLECharacteristic::PROPERTY_READ);
  ch->setValue(value);
}

// ---------------------------------------------------------------------------
void setup() {
  Serial.begin(115200);
  Serial.println("[*] Capitole du Libre 2026 - Challenge 01 - Open GATT");

  Wire.setPins(I2C_SDA, I2C_SCL);
  Wire.begin();
  delay(100);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  } else {
    displayOk = true;
    showTitle();
    lastSwitch = millis();
  }

  BLEDevice::init(DEVICE_NAME);
  pServer = BLEDevice::createServer();
  pServer->setCallbacks(new ServerCallbacks());

  // Device Information
  BLEService* dis = pServer->createService(BLEUUID(DIS_SVC_UUID), 10);
  addReadChar(dis, BLEUUID(DIS_MANUF_UUID), "WOCSA");
  addReadChar(dis, BLEUUID(DIS_MODEL_UUID), "CDL26 Badge");
  addReadChar(dis, BLEUUID(DIS_FW_UUID),    "1.0.0-libre");
  dis->start();

  // libre
  BLEService* libre = pServer->createService(BLEUUID(LIBRE_SVC_UUID), 10);
  addReadChar(libre, BLEUUID(LIBRE_README_UUID),  README_TEXT);
  addReadChar(libre, BLEUUID(LIBRE_LICENSE_UUID), LICENSE_TEXT);
  addReadChar(libre, BLEUUID(LIBRE_AUTHORS_UUID), AUTHORS_TEXT);
  libre->start();

  // source - the flag lives here
  BLEService* source = pServer->createService(BLEUUID(SOURCE_SVC_UUID), 8);
  addReadChar(source, BLEUUID(SOURCE_MAIN_UUID), MAIN_TEXT);
  addReadChar(source, BLEUUID(SOURCE_FLAG_UUID), FLAG);
  source->start();

  // checkin
  BLEService* checkin = pServer->createService(BLEUUID(CHECKIN_SVC_UUID), 12);
  addReadChar(checkin, BLEUUID(CHECKIN_INFO_UUID), CHECKIN_TEXT);

  BLECharacteristic* submitChar =
      checkin->createCharacteristic(CHECKIN_SUBMIT_UUID, BLECharacteristic::PROPERTY_WRITE);
  submitChar->setCallbacks(new SubmitCallback());

  pStatusChar = checkin->createCharacteristic(
      CHECKIN_STATUS_UUID,
      BLECharacteristic::PROPERTY_READ | BLECharacteristic::PROPERTY_NOTIFY);
  // NimBLE adds the 0x2902 CCCD automatically for NOTIFY characteristics
  pStatusChar->setValue(STATUS_WAITING);
  checkin->start();

  // Only "libre" is advertised: the "source" service has to be discovered.
  BLEAdvertising* pAdv = BLEDevice::getAdvertising();
  pAdv->addServiceUUID(LIBRE_SVC_UUID);
  pAdv->setScanResponse(true);
  BLEDevice::startAdvertising();

  Serial.printf("[*] BLE advertising as %s\n", DEVICE_NAME);
}

void loop() {
  if (pendingAccept) {
    pendingAccept = false;
    pendingReject = false;
    pStatusChar->setValue(STATUS_GRANTED);
    pStatusChar->notify();
    mode = MODE_SUCCESS;
    modeStart = millis();
  } else if (pendingReject) {
    pendingReject = false;
    pStatusChar->setValue(STATUS_DENIED);
    pStatusChar->notify();
    if (mode != MODE_SUCCESS) {   // a solved badge stays solved
      mode = MODE_DENIED;
      modeStart = millis();
    }
  }

  if (displayOk) {
    unsigned long now = millis();
    switch (mode) {
      case MODE_SUCCESS:
        if (now - lastFrame >= FRAME_MS) {
          lastFrame = now;
          drawSuccessFrame(now - modeStart);
        }
        break;

      case MODE_DENIED:
        if (now - lastFrame >= FRAME_MS) {
          lastFrame = now;
          showDenied();
        }
        if (now - modeStart >= DENIED_MS) {
          mode = MODE_IDLE;
          lastSwitch = now;
          idleScreens[idleScreen]();
        }
        break;

      case MODE_IDLE:
        if (now - lastSwitch >= IDLE_SCREEN_MS) {
          lastSwitch = now;
          idleScreen = (idleScreen + 1) % IDLE_SCREEN_COUNT;
          idleScreens[idleScreen]();
        }
        break;
    }
  }

  delay(10);
}
