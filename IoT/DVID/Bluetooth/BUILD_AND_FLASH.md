# Build and Flash Guide

## Prerequisites

- [`arduino-cli`](https://arduino.github.io/arduino-cli/) with the ESP32 core installed (`esp32:esp32` ≥ 3.3.7)
- `esptool` bundled with the core at `~/.arduino15/packages/esp32/tools/esptool_py/5.1.0/esptool`
- Required libraries: Adafruit GFX, Adafruit BusIO, Adafruit SSD1306

## Directory layout

Each challenge follows this structure:

```
NN_challenge_name/
  ESP32/
    sketch_name.ino          # classic ESP32 (Bluedroid BLE stack)
    sketch_name_c6.ino       # ESP32-C6 variant (NimBLE BLE stack) — only where API differs
    sketch_name.bin          # compiled binary for classic ESP32
    sketch_name-c6.bin       # compiled binary for ESP32-C6
```

`wocsa_logo.h` lives at the root of this directory and is shared by all sketches via `../../wocsa_logo.h`.

## Compile

The build script mirrors the original directory structure in a temp folder so the relative `../../wocsa_logo.h` include resolves correctly.

### Classic ESP32 (Xtensa, Bluedroid)

```bash
BASE=/path/to/workshop/thcon2026
CHALLENGE_DIR=01_gatt_enumeration   # change per challenge
SKETCH_NAME=gatt_enumeration        # change per challenge

cd "$BASE/$CHALLENGE_DIR"
TMPBASE=$(mktemp -d)
mkdir -p "$TMPBASE/ESP32/$SKETCH_NAME"
cp "ESP32/$SKETCH_NAME.ino" "$TMPBASE/ESP32/$SKETCH_NAME/"
cp "$BASE/wocsa_logo.h" "$TMPBASE/"

arduino-cli compile \
  --fqbn esp32:esp32:esp32 \
  --output-dir /tmp/esp32_build \
  "$TMPBASE/ESP32/$SKETCH_NAME"

cp /tmp/esp32_build/$SKETCH_NAME.ino.bin ESP32/$SKETCH_NAME.bin
rm -rf "$TMPBASE"
```

### ESP32-C6 (RISC-V, NimBLE)

Same as above but use `_c6.ino` as source and `esp32c6` as FQBN:

```bash
arduino-cli compile \
  --fqbn esp32:esp32:esp32c6 \
  --output-dir /tmp/esp32_build \
  "$TMPBASE/ESP32/$SKETCH_NAME"

cp /tmp/esp32_build/$SKETCH_NAME.ino.bin ESP32/$SKETCH_NAME-c6.bin
```

> **Note:** Challenges 04 (`identity_theft`) and 07 (`weak_link`) have a `_c6.ino` variant
> because the Bluedroid and NimBLE BLE APIs differ for those specific callbacks.
> All other challenges compile from the same `.ino` for both targets.

## Detect the serial port

```bash
ls /dev/ttyUSB* /dev/ttyACM* 2>/dev/null
```

When both boards are connected simultaneously, the classic ESP32 and the ESP32-C6 each appear on a separate port (e.g. `/dev/ttyUSB0` and `/dev/ttyUSB1`).

## Flash

Replace `$PORT` with the detected port and `$BIN` with the binary path.

### Classic ESP32

```bash
~/.arduino15/packages/esp32/tools/esptool_py/5.1.0/esptool \
  --chip esp32 --port $PORT --baud 921600 \
  write_flash -z 0x10000 ESP32/$SKETCH_NAME.bin
```

### ESP32-C6

```bash
~/.arduino15/packages/esp32/tools/esptool_py/5.1.0/esptool \
  --chip esp32c6 --port $PORT --baud 921600 \
  write_flash -z 0x10000 ESP32/$SKETCH_NAME-c6.bin
```

> If flashing fails with "No serial data received", hold the **BOOT** button on the board,
> press **RESET**, release BOOT, then retry.
