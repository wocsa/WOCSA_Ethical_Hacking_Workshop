/*
 * BARBHACK 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 01 - Needle in the Sand
 *
 * Two stages:
 *   1. The haystack. 7 services x 7 read-only characteristics = 49 values.
 *      48 are desert/BBQ flavoured decoys, 1 holds the flag. Manual clicking
 *      does not scale - participants must script the enumeration.
 *   2. The check-in. Write the flag back to the submission characteristic.
 *      The badge validates it, notifies GRANTED + a check-in token, and plays
 *      a success animation on the OLED. Show it to the WOCSA booth for a mug.
 *
 * Flag: WOCSA{one_grain_of_sand_in_toulon}
 *
 * Board: classic ESP32 (Bluedroid BLE stack). See needle_in_the_sand_c6.ino
 * for the ESP32-C6 (NimBLE) variant.
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
#define I2C_SDA        15
#define I2C_SCL        4

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

#include "../../../wocsa_logo.h"
#include "../../../barbhack_logo.h"

#define DEVICE_NAME   "BARBHACK26_BLE_01"
#define FLAG          "WOCSA{one_grain_of_sand_in_toulon}"
#define CHECKIN_TOKEN "BARBHACK26_CHECKIN_OK"

// ---------------------------------------------------------------------------
// Stage 1 - the haystack
// ---------------------------------------------------------------------------
// Services : bbb0000S-0000-1000-8000-00805f9b34fb   (S = 1..7)
// Values   : bbb0000S-000C-1000-8000-00805f9b34fb   (C = 1..7)

#define SVC_COUNT  7
#define CHAR_COUNT 7

static const char* SERVICE_UUIDS[SVC_COUNT] = {
  "bbb00001-0000-1000-8000-00805f9b34fb",
  "bbb00002-0000-1000-8000-00805f9b34fb",
  "bbb00003-0000-1000-8000-00805f9b34fb",
  "bbb00004-0000-1000-8000-00805f9b34fb",
  "bbb00005-0000-1000-8000-00805f9b34fb",
  "bbb00006-0000-1000-8000-00805f9b34fb",
  "bbb00007-0000-1000-8000-00805f9b34fb"
};

static const char* CHAR_UUIDS[SVC_COUNT][CHAR_COUNT] = {
  { "bbb00001-0001-1000-8000-00805f9b34fb", "bbb00001-0002-1000-8000-00805f9b34fb",
    "bbb00001-0003-1000-8000-00805f9b34fb", "bbb00001-0004-1000-8000-00805f9b34fb",
    "bbb00001-0005-1000-8000-00805f9b34fb", "bbb00001-0006-1000-8000-00805f9b34fb",
    "bbb00001-0007-1000-8000-00805f9b34fb" },
  { "bbb00002-0001-1000-8000-00805f9b34fb", "bbb00002-0002-1000-8000-00805f9b34fb",
    "bbb00002-0003-1000-8000-00805f9b34fb", "bbb00002-0004-1000-8000-00805f9b34fb",
    "bbb00002-0005-1000-8000-00805f9b34fb", "bbb00002-0006-1000-8000-00805f9b34fb",
    "bbb00002-0007-1000-8000-00805f9b34fb" },
  { "bbb00003-0001-1000-8000-00805f9b34fb", "bbb00003-0002-1000-8000-00805f9b34fb",
    "bbb00003-0003-1000-8000-00805f9b34fb", "bbb00003-0004-1000-8000-00805f9b34fb",
    "bbb00003-0005-1000-8000-00805f9b34fb", "bbb00003-0006-1000-8000-00805f9b34fb",
    "bbb00003-0007-1000-8000-00805f9b34fb" },
  { "bbb00004-0001-1000-8000-00805f9b34fb", "bbb00004-0002-1000-8000-00805f9b34fb",
    "bbb00004-0003-1000-8000-00805f9b34fb", "bbb00004-0004-1000-8000-00805f9b34fb",
    "bbb00004-0005-1000-8000-00805f9b34fb", "bbb00004-0006-1000-8000-00805f9b34fb",
    "bbb00004-0007-1000-8000-00805f9b34fb" },
  { "bbb00005-0001-1000-8000-00805f9b34fb", "bbb00005-0002-1000-8000-00805f9b34fb",
    "bbb00005-0003-1000-8000-00805f9b34fb", "bbb00005-0004-1000-8000-00805f9b34fb",
    "bbb00005-0005-1000-8000-00805f9b34fb", "bbb00005-0006-1000-8000-00805f9b34fb",
    "bbb00005-0007-1000-8000-00805f9b34fb" },
  { "bbb00006-0001-1000-8000-00805f9b34fb", "bbb00006-0002-1000-8000-00805f9b34fb",
    "bbb00006-0003-1000-8000-00805f9b34fb", "bbb00006-0004-1000-8000-00805f9b34fb",
    "bbb00006-0005-1000-8000-00805f9b34fb", "bbb00006-0006-1000-8000-00805f9b34fb",
    "bbb00006-0007-1000-8000-00805f9b34fb" },
  { "bbb00007-0001-1000-8000-00805f9b34fb", "bbb00007-0002-1000-8000-00805f9b34fb",
    "bbb00007-0003-1000-8000-00805f9b34fb", "bbb00007-0004-1000-8000-00805f9b34fb",
    "bbb00007-0005-1000-8000-00805f9b34fb", "bbb00007-0006-1000-8000-00805f9b34fb",
    "bbb00007-0007-1000-8000-00805f9b34fb" }
};

// The flag lives here. Everything else is sand.
#define FLAG_SVC  3   // 0-based -> service 4
#define FLAG_CHAR 5   // 0-based -> characteristic 6

// 48 decoys. Two of them contain "WOCSA" without the WOCSA{...} form, so a
// naive `if b"WOCSA" in value` search returns false positives.
static const char* decoys[SVC_COUNT * CHAR_COUNT - 1] = {
  "sensor_id=0x2A",       "grill_temp=214C",      "charcoal=lit",
  "skull=longhorn",       "cactus_count=2",       "sunset=19:34",
  "rocket=launched",      "spatula_a=clean",      "spatula_b=greasy",
  "bbq=medium_rare",      "sand_grains=many",     "dune_height=4m",
  "WOCSA_workshop=ble",   "toulon=neptune",       "battery=91%",
  "rssi=-63dBm",          "mtu=23",               "channel=39",
  "adv_interval=100ms",   "conn_latency=0",       "bond=false",
  "encrypt=none",         "firmware=2.6.0",       "uid=0xBEEF",
  "checksum=0x5A",        "mode=desert",          "thirst=high",
  "beer=cold",            "flag=not_here",        "flag_format=curly_braces",
  "wocsa.org=visit_us",   "hint=automate_it",     "temp_probe=meat",
  "smoke=mesquite",       "coals=glowing",        "petanque=boules",
  "cochonnet=0x01",       "sunscreen=spf50",      "shade=none",
  "water=1.5L",           "gps=43.12N,5.93E",     "altitude=12m",
  "wind=8kt",             "humidity=61%",         "uptime=3h12m",
  "heap_free=118KB",      "tx_power=0dBm",        "sleep=disabled"
};

// ---------------------------------------------------------------------------
// Stage 2 - the check-in desk
// ---------------------------------------------------------------------------
#define SUBMIT_SVC_UUID     "bbb000ff-0000-1000-8000-00805f9b34fb"
#define SUBMIT_INFO_UUID    "bbb000ff-00f0-1000-8000-00805f9b34fb"  // read
#define SUBMIT_WRITE_UUID   "bbb000ff-00f1-1000-8000-00805f9b34fb"  // write
#define SUBMIT_STATUS_UUID  "bbb000ff-00f2-1000-8000-00805f9b34fb"  // read + notify

#define SUBMIT_INFO_TEXT "Write the flag here to check in."
#define STATUS_WAITING   "WAITING"
#define STATUS_DENIED    "DENIED"
#define STATUS_GRANTED   "GRANTED:" CHECKIN_TOKEN

BLEServer*         pServer     = nullptr;
BLECharacteristic* pStatusChar = nullptr;

// Set by the BLE write callback, consumed by loop(). Callbacks stay short so
// the BLE stack is never blocked by the OLED animation.
volatile bool pendingAccept = false;
volatile bool pendingReject = false;

// ---------------------------------------------------------------------------
// Display state
// ---------------------------------------------------------------------------
bool displayOk = false;

enum DisplayMode { MODE_IDLE, MODE_DENIED, MODE_SUCCESS };
DisplayMode mode = MODE_IDLE;

#define IDLE_SCREEN_MS 6000
#define DENIED_MS      1500
#define FRAME_MS       50

uint8_t       idleScreen  = 0;
unsigned long lastSwitch  = 0;
unsigned long lastFrame   = 0;
unsigned long modeStart   = 0;

void showTitle() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("BARBHACK 2026"));
  display.println(F("Needle in the Sand"));
  display.println();
  display.println(F("49 values, 1 flag."));
  display.println(F("Script it, submit it,"));
  display.println(F("win a WOCSA mug."));
  display.println();
  display.println(F("BARBHACK26_BLE_01"));
  display.display();
}

void showWocsaLogo() {
  display.clearDisplay();
  display.drawBitmap(0, 0, epd_bitmap_wocsa_logo, 128, 64, SSD1306_WHITE);
  display.display();
}

// The official logo carries its own BARBHACK 2026 wordmark, so this screen is
// just the logo, centred and undecorated.
void showBarbhackLogo() {
  display.clearDisplay();
  display.drawBitmap(32, 0, epd_bitmap_barbhack_logo, 64, 64, SSD1306_WHITE);
  display.display();
}

void (*idleScreens[])() = { showTitle, showWocsaLogo, showBarbhackLogo };
const uint8_t IDLE_SCREEN_COUNT = sizeof(idleScreens) / sizeof(idleScreens[0]);

void showDenied() {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(2);
  display.setCursor(16, 16);
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
//   3.5-6.0s  BARBHACK 2026 / Toulon credits scrolling up
//   >6.0s     latched trophy screen (emblem + banner)
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
      display.setCursor(22, 12);
      display.print(F("FLAG"));
      display.setCursor(16, 32);
      display.print(F("FOUND"));
    }
    display.drawRect(0, 0, 128, 64, SSD1306_WHITE);
    display.drawRect(2, 2, 124, 60, SSD1306_WHITE);

  } else if (elapsed < 6000) {
    int y = 64 - (int)((elapsed - 3500) / 40);   // scroll up over 2.5 s
    display.setTextSize(1);
    display.setCursor(16, y);
    display.print(F("BARBHACK 2026"));
    display.setCursor(28, y + 14);
    display.print(F("Toulon, FR"));
    display.setCursor(4, y + 32);
    display.print(F("Claim your WOCSA"));
    display.setCursor(28, y + 42);
    display.print(F("mug at the"));
    display.setCursor(34, y + 52);
    display.print(F("booth!"));

  } else {
    display.drawBitmap(0, 0, epd_bitmap_barbhack_logo, 64, 64, SSD1306_WHITE);
    display.setTextSize(1);
    display.setCursor(68, 8);
    display.print(F("FLAG"));
    display.setCursor(68, 18);
    display.print(F("FOUND!"));
    display.setCursor(68, 36);
    display.print(F("Get your"));
    display.setCursor(68, 46);
    display.print(F("mug ->"));
    display.drawRect(64, 0, 64, 64, SSD1306_WHITE);
  }

  display.display();
}

// ---------------------------------------------------------------------------
// BLE callbacks
// ---------------------------------------------------------------------------
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

// ---------------------------------------------------------------------------
void setup() {
  Serial.begin(115200);
  Serial.println("[*] BarbHack 2026 - Challenge 01 - Needle in the Sand");

  Wire.begin(I2C_SDA, I2C_SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  } else {
    displayOk = true;
    showTitle();
    lastSwitch = millis();
  }

  BLEDevice::init(DEVICE_NAME);
  pServer = BLEDevice::createServer();

  int decoyIdx = 0;
  for (int s = 0; s < SVC_COUNT; s++) {
    // 1 service handle + 2 handles per characteristic, rounded up.
    BLEService* svc = pServer->createService(BLEUUID(SERVICE_UUIDS[s]), 2 * CHAR_COUNT + 2);
    for (int c = 0; c < CHAR_COUNT; c++) {
      const char* value = (s == FLAG_SVC && c == FLAG_CHAR) ? FLAG : decoys[decoyIdx++];
      BLECharacteristic* ch =
          svc->createCharacteristic(CHAR_UUIDS[s][c], BLECharacteristic::PROPERTY_READ);
      ch->setValue(value);
    }
    svc->start();
    Serial.printf("[*] Service %d started (%d characteristics)\n", s + 1, CHAR_COUNT);
  }

  // Check-in desk
  BLEService* submit = pServer->createService(BLEUUID(SUBMIT_SVC_UUID), 12);

  BLECharacteristic* info =
      submit->createCharacteristic(SUBMIT_INFO_UUID, BLECharacteristic::PROPERTY_READ);
  info->setValue(SUBMIT_INFO_TEXT);

  BLECharacteristic* submitChar =
      submit->createCharacteristic(SUBMIT_WRITE_UUID, BLECharacteristic::PROPERTY_WRITE);
  submitChar->setCallbacks(new SubmitCallback());

  pStatusChar = submit->createCharacteristic(
      SUBMIT_STATUS_UUID,
      BLECharacteristic::PROPERTY_READ | BLECharacteristic::PROPERTY_NOTIFY);
  pStatusChar->addDescriptor(new BLE2902());   // CCCD, required by Bluedroid
  pStatusChar->setValue(STATUS_WAITING);
  submit->start();

  BLEAdvertising* pAdv = BLEDevice::getAdvertising();
  pAdv->addServiceUUID(SERVICE_UUIDS[0]);
  pAdv->setScanResponse(true);
  BLEDevice::startAdvertising();

  Serial.printf("[*] BLE advertising as %s - %d characteristics exposed\n",
                DEVICE_NAME, SVC_COUNT * CHAR_COUNT);
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
