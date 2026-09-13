# BARBHACK 2026 - DVID/WOCSA Bluetooth Challenge

<p align="center">
  <img src="https://avatars.githubusercontent.com/wocsa" alt="WOCSA Logo" />
</p>

This challenge is run by WOCSA at **BarbHack 2026** — Saturday 29 August 2026,
Palais des Congrès Neptune, Toulon, France. *"French Southernmost Hacking Event"*,
organised by the HackerZVoice association.

It is a standalone booth challenge, separate from the ten-challenge
[THCon 2026 workshop](../) in the parent directory. Bring your laptop or your phone,
find the flag on the badge, and check in.

## Credits

* DVID: https://dvid.eu
* WOCSA: https://wocsa.org
* BarbHack: https://www.barbhack.fr/2026/

## 🏆 The prize

**Solve the challenge, show the "FLAG FOUND" screen on the badge at the WOCSA booth,
and walk away with a WOCSA mug.**

## Challenges

| # | Name | Tier | Difficulty | What you learn |
|---|------|------|-----------|----------------|
| 01 | [Needle in the Sand](./01_needle_in_the_sand/) | Intermediate | ⭐⭐⭐ | Automate GATT enumeration at scale, then write the flag back and catch the notification |

## How it runs at the booth

1. The badge idles, rotating three screens every 6 seconds: the challenge title and
   description, the WOCSA logo, and the BarbHack 2026 emblem.
2. A participant enumerates the GATT database and finds the flag among 49 values.
3. They write the flag to the check-in characteristic. The badge validates it,
   notifies `GRANTED:BARBHACK26_CHECKIN_OK`, and plays a success animation that
   latches on a "FLAG FOUND" trophy screen.
4. Booth staff hand over the mug and press **RST** to reset the badge for the next
   participant.

A wrong submission flashes `DENIED` for 1.5 s and returns to the idle rotation — the
badge can be hammered at safely.

## Hardware

Same setup as the THCon workshop: a DVID / ESP32 board with an SSD1306 128x64 I2C
OLED. Both a classic ESP32 and an ESP32-C6 build are provided
(`needle_in_the_sand.bin` / `needle_in_the_sand_c6.bin`). See
[BUILD_AND_FLASH.md](../BUILD_AND_FLASH.md) for the build and flash commands.

## Flag Format

`WOCSA{...}` — same as the rest of the repository.
