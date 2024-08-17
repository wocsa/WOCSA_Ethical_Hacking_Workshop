# Warning
This workshop is for educational purposes only.
Ethical hacking is conducted with the explicit permission of the system owner to improve security.

To discuss about this workshop you can join our [WOCSA discord](https://discord.gg/P2YH3ubC) in ETHICAL HACKING WORKSHOPS category.

# DEFCON 32
This workshop use the [DEFCON 32 Badge](https://www.tomshardware.com/raspberry-pi/raspberry-pi-pico/raspberry-pi-pico-2-developer-demonstrates-running-doom-on-rp2350-powered-def-con-32-badge)  as hardware material.

## Related links
[Badge Creator project description by Mar Williams](https://marwilliams.art/blogs/projects/def-con-32-badges)
[Official DEFCON32 Badge Repository](https://media.defcon.org/DEF%20CON%2032/DEF%20CON%2032%20badge/)
[Firmware Creator project description by DimitryGr](https://dmitry.gr/?r=06.+Thoughts&proj=11.+RP2350#_TOC_938ebfd37c7b6cb6656c4bcb87fca874)
[GB interface to badge extra features by DimitryGr](https://docs.google.com/document/d/1COY5n0HhBcBq7ILwsKtOjV-_0-arNPkk_qBH9PI9fms/edit#heading=h.gldd1ycy1odt)
[Badge Writeup by Dietcholoatebar](https://docs.google.com/document/d/1Jff1UbKaRGoHoAug1c0r6a4Y-MYruvgVORGG8c63sNQ/edit#heading=h.38ffvio0tkya)
[RP2350 Hacking Challenge](https://github.com/raspberrypi/rp2350_hacking_challenge)
[Raspberry PICO Official doc](https://datasheets.raspberrypi.com/pico/getting-started-with-pico.pdf)

### Firmware (including bare-metal Emulator uGB)
[Creator's Firmware Source Code 1.5 by DimitryGr](http://dmitry.gr/images/defcon_code_1.5.0.tar.bz2)
[Creator's Firmware Binary 1.5 by DmitryGr](http://dmitry.gr/images/defcon_update_1.5.0.tar.bz2)
[Creator's Firmware Binary and UF2 1.6 by DmitryGr](https://discord.com/channels/867438418212683796/1262488625799495732/1271971778985590805)
[Unofficial Repository of Firmwares by Jaku](https://github.com/jaku/DEFCON-32-BadgeFirmware)
[Firmware generator by Jaku](https://defrom.lol/)
[Custom firmware using all LEDs by Calvin-LL](https://github.com/Calvin-LL/defcon-32-badge-flashy-rom)
[Raspberry PICO SDK Header for the DEFCON32 Badge](https://github.com/raspberrypi/pico-sdk/blob/master/src/boards/include/boards/defcon32_badge.h)

### Alternative Operating System
[Creator's rePalm Operating System](https://dmitry.gr/?r=05.Projects&proj=27.%20rePalm#_TOC_0734fd58b98b17e23027547eec1258f5)

### SD Card
[Unofficial SD Card files repository by BillyBriant](https://github.com/billyjbryant/DC32-Badge-Hack/tree/main/DC32BadgeSD)

### Gameboy Color ROMs
[DEFCON 32 Official Game ROM by CosmicBonBon](https://github.com/CosmicBonBon/DC32BadgeGame)
[Game Development IDE used for the Official ROM](https://github.com/chrismaltby/gb-studio/)
[Windows Bluescreen ROM](https://github.com/rootabeta/BSoDEFCON/tree/main)

# Troubleshoot first then play
## SD Card issue
### FAT filesystem corruption
The FAT system contains error in the SD Card.
But the SD Card could by physically damaged too.

1. Get a new SD card. The con ones are cheap garbage. (NOTE: If you are using the con SD card, use firmware 1.5. Otherwise go for 1.4 linked below)
2. Format using the SD Formatter from the sdcard.org website
3. Delete any existing partition using diskmgmt
4. Do not use "fast formatting" when creating a new partition
5. It might've helped to start with a 2GB FAT16 partition first, but now I've expanded to a 4GB FAT32 partition
6. I did all this on Windows, using the g firmware
7. Make sure to put your roms in the ROM folder
[source](https://discord.com/channels/867438418212683796/1262488625799495732/1271261374537797798)

Note: disk copy from original SD Card is possible using ddrescue

### Game Save issue
1. Create a new folder on the root of the SD card named "SAVE"
2. Install firmware 1.5 (But the game crash when you take the stairs)
[source](https://discord.com/channels/867438418212683796/1262488625799495732/1271261374537797798)

3. copy original ROM on SD CARD then load it to have ROM without bug

### Filenames unsupported 
Only FAT filename are supported even on FAT32 filesystem.
Use tool to rename filenames and sanitize them like detox.



