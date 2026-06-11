/*
 * THCON 2026 - DVID/WOCSA Bluetooth Workshop
 * Challenge 05 - Needle in a Haystack
 *
 * The ESP32 exposes 5 services each containing 6 characteristics.
 * 29 characteristics contain decoy strings; 1 contains the flag.
 * Participants must write a script to iterate and find WOCSA{...}.
 *
 * Flag: WOCSA{scripting_beats_manual_recon}
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

#define DEVICE_NAME "THCON26_BLE_05"
#define FLAG        "WOCSA{scripting_beats_manual_recon}"

// 5 services
#define SVC1 "aaa00001-0000-1000-8000-00805f9b34fb"
#define SVC2 "aaa00002-0000-1000-8000-00805f9b34fb"
#define SVC3 "aaa00003-0000-1000-8000-00805f9b34fb"
#define SVC4 "aaa00004-0000-1000-8000-00805f9b34fb"
#define SVC5 "aaa00005-0000-1000-8000-00805f9b34fb"

// 30 characteristics (6 per service)
// Format: aaa0000X-00YY-1000-8000-00805f9b34fb  (X=service, YY=char index)
#define C(svc, idx) "aaa0000" #svc "-00" #idx "0-1000-8000-00805f9b34fb"

// Decoy strings
const char* decoys[] = {
  "sensor_data_42",
  "temp=23.4C",
  "status=idle",
  "version=1.0.3",
  "uid=0xDEAD",
  "checksum=0xFF",
  "config=default",
  "mode=passive",
  "battery=87%",
  "rssi=-68dBm",
  "interval=500ms",
  "threshold=10",
  "offset=0x00",
  "gain=1.5",
  "filter=none",
  "channel=37",
  "mtu=23",
  "latency=0",
  "timeout=100",
  "conn_id=3",
  "adv_type=ind",
  "bond=false",
  "encrypt=none",
  "auth=none",
  "heart_rate=72",
  "steps=4821",
  "calories=312",
  "distance=3.2km",
  "sleep=6h42m"
};

BLEServer* pServer = nullptr;

BLECharacteristic* addChar(BLEService* svc, const char* uuid, const char* value) {
  BLECharacteristic* c = svc->createCharacteristic(uuid, BLECharacteristic::PROPERTY_READ);
  c->setValue(value);
  return c;
}

void showChallengeName() {
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(0, 0);
  display.println(F("THCON 2026"));
  display.println(F("Challenge 05"));
  display.println();
    display.println(F("Needle in a"));
    display.println(F("Haystack"));;
  display.display();
}

void setup() {
  Serial.begin(115200);
  Serial.println("[*] Challenge 05 - Needle in a Haystack");

  Wire.begin(I2C_SDA, I2C_SCL);
  if (!display.begin(SSD1306_SWITCHCAPVCC, SCREEN_ADDRESS)) {
    Serial.println(F("SSD1306 allocation failed"));
  } else {
    displayOk = true;
    showChallengeName();
    lastSwitch = millis();
  }

  BLEDevice::init(DEVICE_NAME);
  pServer = BLEDevice::createServer();

  // Service 1: chars 0-5
  BLEService* s1 = pServer->createService(BLEUUID(SVC1), 20);
  addChar(s1, "aaa00001-0001-1000-8000-00805f9b34fb", decoys[0]);
  addChar(s1, "aaa00001-0002-1000-8000-00805f9b34fb", decoys[1]);
  addChar(s1, "aaa00001-0003-1000-8000-00805f9b34fb", decoys[2]);
  addChar(s1, "aaa00001-0004-1000-8000-00805f9b34fb", decoys[3]);
  addChar(s1, "aaa00001-0005-1000-8000-00805f9b34fb", decoys[4]);
  addChar(s1, "aaa00001-0006-1000-8000-00805f9b34fb", decoys[5]);
  s1->start();

  // Service 2: chars 6-11
  BLEService* s2 = pServer->createService(BLEUUID(SVC2), 20);
  addChar(s2, "aaa00002-0001-1000-8000-00805f9b34fb", decoys[6]);
  addChar(s2, "aaa00002-0002-1000-8000-00805f9b34fb", decoys[7]);
  addChar(s2, "aaa00002-0003-1000-8000-00805f9b34fb", decoys[8]);
  addChar(s2, "aaa00002-0004-1000-8000-00805f9b34fb", decoys[9]);
  addChar(s2, "aaa00002-0005-1000-8000-00805f9b34fb", decoys[10]);
  addChar(s2, "aaa00002-0006-1000-8000-00805f9b34fb", decoys[11]);
  s2->start();

  // Service 3: chars 12-17
  BLEService* s3 = pServer->createService(BLEUUID(SVC3), 20);
  addChar(s3, "aaa00003-0001-1000-8000-00805f9b34fb", decoys[12]);
  addChar(s3, "aaa00003-0002-1000-8000-00805f9b34fb", decoys[13]);
  addChar(s3, "aaa00003-0003-1000-8000-00805f9b34fb", decoys[14]);
  addChar(s3, "aaa00003-0004-1000-8000-00805f9b34fb", decoys[15]);
  addChar(s3, "aaa00003-0005-1000-8000-00805f9b34fb", FLAG);  // <-- FLAG HERE
  addChar(s3, "aaa00003-0006-1000-8000-00805f9b34fb", decoys[16]);
  s3->start();

  // Service 4: chars 17-22
  BLEService* s4 = pServer->createService(BLEUUID(SVC4), 20);
  addChar(s4, "aaa00004-0001-1000-8000-00805f9b34fb", decoys[17]);
  addChar(s4, "aaa00004-0002-1000-8000-00805f9b34fb", decoys[18]);
  addChar(s4, "aaa00004-0003-1000-8000-00805f9b34fb", decoys[19]);
  addChar(s4, "aaa00004-0004-1000-8000-00805f9b34fb", decoys[20]);
  addChar(s4, "aaa00004-0005-1000-8000-00805f9b34fb", decoys[21]);
  addChar(s4, "aaa00004-0006-1000-8000-00805f9b34fb", decoys[22]);
  s4->start();

  // Service 5: chars 23-28
  BLEService* s5 = pServer->createService(BLEUUID(SVC5), 20);
  addChar(s5, "aaa00005-0001-1000-8000-00805f9b34fb", decoys[23]);
  addChar(s5, "aaa00005-0002-1000-8000-00805f9b34fb", decoys[24]);
  addChar(s5, "aaa00005-0003-1000-8000-00805f9b34fb", decoys[25]);
  addChar(s5, "aaa00005-0004-1000-8000-00805f9b34fb", decoys[26]);
  addChar(s5, "aaa00005-0005-1000-8000-00805f9b34fb", decoys[27]);
  addChar(s5, "aaa00005-0006-1000-8000-00805f9b34fb", decoys[28]);
  s5->start();

  BLEAdvertising* pAdv = BLEDevice::getAdvertising();
  pAdv->addServiceUUID(SVC1);
  pAdv->setScanResponse(true);
  BLEDevice::startAdvertising();

  Serial.println("[*] BLE advertising started - 30 characteristics exposed");
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
