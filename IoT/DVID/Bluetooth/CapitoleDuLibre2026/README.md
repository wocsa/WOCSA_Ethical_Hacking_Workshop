# CAPITOLE DU LIBRE 2026 - DVID/WOCSA Bluetooth Challenge

<p align="center">
  <img src="https://capitoledulibre.org/static/img/logo-capitoledulibre-horizontal.svg" alt="Capitole du Libre logo" width="480" />
</p>

This challenge is run by WOCSA at **Capitole du Libre 2026**, 14-15 November 2026,
ENSEEIHT (INP-N7), Toulouse, France — the free software event of Occitanie,
organised by the Toulibre association.

It is a standalone stand challenge for beginners, separate from the ten-challenge
[THCon 2026 workshop](../) in the parent directory. Bring your laptop or your phone,
find the flag on the badge and check in.

## Credits

* DVID: https://dvid.eu
* WOCSA: https://wocsa.org
* Capitole du Libre: https://capitoledulibre.org

## Challenges

| # | Name | Tier | Difficulty | What you learn |
|---|------|------|-----------|----------------|
| 01 | [Open GATT](./01_open_gatt/) | Beginner | ⭐ | Scan, connect, enumerate every GATT service (not only the advertised one), read the flag, write it back and catch the notification |

## How it runs at the stand

1. The badge idles, rotating three screens every 6 seconds: the challenge title, the
   WOCSA logo and the Capitole du Libre wordmark.
2. A participant connects, discovers the four services and reads the flag from the
   `source` service — which is **not** in the advertisement.
3. They write the flag to the check-in characteristic. The badge notifies
   `GRANTED:CDL26_CHECKIN_OK` and plays a success animation that latches on an
   alternating Capitole du Libre logo / "FLAG FOUND!" screen.
4. Stand staff congratulate them and press **RST** to reset the badge for the next
   participant.

A wrong submission flashes `DENIED` for 1.5 s and returns to the idle rotation.

## Hardware

ESP32-C6 with an SSD1306 128x64 I2C OLED (SDA 6, SCL 7) — the same DVID board as the
THCon workshop. Prebuilt image: `01_open_gatt/ESP32/open_gatt_c6.bin`. See
[BUILD_AND_FLASH.md](../BUILD_AND_FLASH.md) for build and flash commands.

## Flag Format

`WOCSA{...}` — same as the rest of the repository.
