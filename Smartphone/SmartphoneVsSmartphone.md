# Smartphone VS Smartphone

## Attacker' Smartphone
### Device Profile
- Google Nexus 6P
- Android 10
- LineageOS Angler 10 (`200805.001`)
- Kali NetHunter `2025.2`
- Current IP address: `192.168.2.245`

### Hardware
- Dongle USB Asus BT540
- Dongle USB Realtek RTL8812BU

### Access
- SSH: `ssh kali@192.168.2.245`
- Login: `kali`
- Password: `kali`

## Victim Smartphone
### Device Profile
- Samsung Galaxy A13
- One UI Core 5.1
- Android 13
- Latest system update: 01 October 2023
- Kernel version: 4.19.191
- Knox: 3.9
- Knox API level: 36
- Android security patch level: 01 November 2023
- Current IP address: `192.168.2.170`
- Bluetooth MAC address: `40:5E:F6:A2:D7:75`
- Bluetooth name: `Galaxy A13`
- Assessment mode: black box

### Bluetooth Assessment Notes
- The victim smartphone was observed as discoverable over classic Bluetooth during lab scanning.
- Observed Bluetooth identity:
  - Name: `Galaxy A13`
  - MAC: `40:5E:F6:A2:D7:75`
- The device is running Android 13 with Samsung One UI Core 5.1 and an Android security patch level of 01 November 2023.
- No exploitation steps are documented here. The workshop should use this device only for authorised exposure validation, defensive analysis, and hardening demonstrations.

### Defensive Bluetooth Checklist
- Confirm whether Bluetooth discoverability is permanently enabled or only enabled during pairing.
- Review paired devices and remove any unknown or stale trust relationships.
- Disable Bluetooth when not actively required for the workshop scenario.
- Verify the phone is fully updated beyond the documented baseline before any public demonstration.
- Minimise enabled Bluetooth-dependent features that are not needed for the lab.
- Monitor for unexpected pairing prompts, repeated connection attempts, or unusual Bluetooth service behaviour.
- Record whether the device advertises different identifiers over time or keeps a stable public identity.

### Workshop-Safe Validation Ideas
- Check whether the device remains discoverable after leaving the Bluetooth settings screen.
- Observe whether the Bluetooth name and MAC remain stable across reboots or Bluetooth resets.
- Test whether pairing requires explicit local user approval every time.
- Compare behaviour with Bluetooth enabled versus disabled to show risk reduction.
- Document the difference between classic Bluetooth discovery and BLE visibility in the lab.

### Hardening Guidance
- Keep Bluetooth disabled by default on the victim smartphone outside the demonstration window.
- Use the latest available Samsung firmware and security patches for the exact device model.
- Avoid unnecessary pairing with untrusted accessories or testing devices.
- Remove old pairings after the workshop.
- Reboot and review wireless settings after each lab session to return the phone to a known-good state.

### Install
This documentation is a fork from (Nethunter on a Nexus 6P using LineageOs Android 10)[(https://github.com/TheMMcOfficial/Nethunter-Nexus_6P-LOS10] with some updates.

In case your network devices (Wifi, Bluetooth, NFC,...) are not recognised it could be unsupported feature of the Kali Kernel. In that case you can use external dongle which are supported [check wifi supported cards here](https://www.kali.org/docs/nethunter/wireless-cards/).

Choose installation related to your Smartphone on [Kali Nethunter Install Page](https://www.kali.org/docs/nethunter/).The 
best support is with the Google Nexus 5.

# Nethunter on a Nexus 6P using LineageOs (Android 10)
Instructions to get Nethunter on a Nexus 6P Using Android 10 (LineageOS). You should also be able to use the internal wifi card in monitor mode and maybe packages injection using Nexmon. 

## Prerequisites:
A Nexus 6P, all the files to install/flash, a computer with the ADB tool installed on it. 

For the Nexmon Tools and the Lineage OS version you can find them here: https://gitlab.com/kalilinux/nethunter/build-scripts/kali-nethunter-devices/-/issues/236

Magisk APK: https://magisk.me/apk/

Magisk ZIP: https://magisk.me/zip/

Nethunter: https://www.kali.org/kali-linux-nethunter/



## How to install Nethunter and LineageOS

1- boot/reboot The Nexus 6P in fastboot 
```adb reboot bootloader```

2- flash the recovery partition
```fastboot flash recovery twrp-3.3.1.0-FBE-10-angler.img```

3- boot the phone in TWRP

4- flush system, data and cache partition in TWRP

5- reboot in the bootloader
```adb reboot bootloader```

6- flash radio (radio-angler-angler-03.88.img), vendor (vendor-angler-opm7.181205.001.img), bootloader (bootloader-angler-angler-03.84.img)
```
fastboot flash radio radio-angler-angler-03.88.img
fastboot flash vendor vendor-angler-opm7.181205.001.img 
fastboot flash bootloader bootloader-angler-angler-03.84.img
```

7- boot in TWRP again and push the OS
```adb push lineage-17.1-20200819-UNOFFICIAL-angler.zip /sdcard/```

8- install it in TWRP

9- reboot in bootloader and push the vendor.squashfs

```
adb reboot bootloader
fastboot flash vendor vendor.squashfs
```

10- In TWRP do those commands

```
adb push fstab.angler /sdcard/
adb shell "twrp mount /system_root && twrp remountrw /system_root && cp /sdcard/fstab.angler /system_root/"
```

11- format data in TWRP

12- reboot the system and setup android

13- Install Magisk (take note this version of Magisk may be out of date you should take the latest release)

```adb push Magisk-v29.0.apk /sdcard/```

14- boot to Android and install the MagiskManager app

```adb push Magisk-v29.0.apk /sdcard/```

If you still have a message in the Magisk app "Require additional Setup".

From the Phone (via adb/fastboot)

If you don’t have the ROM ZIP:

Connect phone with USB debugging enabled.

Run:
```adb shell getprop ro.boot.slot_suffix```

(Nexus 6P usually doesn’t use A/B slots, so just one boot partition.)

for the next command we need to give the root permission for adb in setting->developer tick root adb.


Then dump the boot partition:

```adb shell
su        # if root works tick allow adb root in developper options
dd if=/dev/block/bootdevice/by-name/boot of=/sdcard/boot.img
exit
exit
adb pull /sdcard/boot.img```
Now you have your current boot image.

In Magisk app 
Click on install in Magisk top part.
Then tick recovery then next
then clip patch from file.
Select the boot.img in sdcard 
Magisk will create a "magisk_patched-*_*.img" file usually in Download folder.
Move the file into *sdcard* place (usually root of the device).
```adb pull /sdcard/magisk_patched-*_*.img```

Now upload the patched boot image to the boot partition.
```adb shell
su        # if root works
dd if=/sdcard/magisk_patched-*_*.img of=/dev/block/bootdevice/by-name/boot
exit
exit```

reboot the device.
```adb reboot system```

Open the Magisk app the message might disappear.



15- push nethunter to the sdcard

```adb push kali-nethunter-2025.2-angler-los-ten-full.zip /sdcard/```

In Magisk App click extensions
click on install from file
select the file *kali-nethunter-2025.2-angler-los-ten-full.zip*
The installation can take more thant 30 minutes.

16- copy the new libs over the existing

```
adb push libnexmon* /sdcard/
adb shell "su -c 'mount -o rw,remount / && cp /sdcard/libnexmonkali.so /system/lib64/kalilibnexmon.so && cp /sdcard/libnexmon.so /system/lib64/'"
```

for the last command we need to give the root permission for adb in setting->developer tools and in Magisk

```
adb push nexutil /sdcard/
adb shell "su -c 'mount -o rw,remount / && cp /sdcard/nexutil /system/xbin/'"
adb shell "su -c 'chmod +x /system/xbin/nexutil && chmod +x /system/lib64/kalilibnexmon.so && chmod +x /system/lib64/libnexmon.so'"
```

17- For the support of multiple external wifi cards go to magisk and install nethunter-magisk-wifi-firmware by rithvikvibhu. You can see the list of card supported here: https://github.com/rithvikvibhu/nh-magisk-wifi-firmware


If you can't start sshd just run in kali linux terminal:
```ssh-keygen -A```

If you face the following error using *apt* command "Failed to take /etc/passwd lock: Invalid argument" try this workaround:
```bash
sudo sed -i -e '/systemd-sysusers/s/\.conf$/.conf || true/' /var/lib/dpkg/info/*.postinst
sudo dpkg --configure -a
sudo apt -f install
```
## Sources:
https://forum.xda-developers.com/nexus-6p/development/rom-kali-nethunter-huawei-nexus-6p-oreo-t4079087/

https://forum.xda-developers.com/nexus-6p/orig-development/rom-lineageos-17-0-nexus-6p-angler-t4012099

https://gitlab.com/kalilinux/nethunter/build-scripts/kali-nethunter-devices/-/issues/236

https://gitlab.com/kalilinux/nethunter/build-scripts/kali-nethunter-devices/-/issues/242

https://hackyourmom.com/en/kibervijna/chastyna-11-yak-vykorystovuvaty-bluetooth-arsenal-u-kali-nethunter-dlya-atak-i-prosluhovuvannya-prystroyiv/
[Nexmon](https://github.com/yesimxev/nethunter_files)
[vendor.squashfs](https://github.com/yesimxev/nethunter_files/tree/main/angler)
[bettercap tutorial](https://medium.com/@redfanatic7/bettercap-hacking-attacks-on-bluetooth-devices-32d72e30b583)
[drozer](https://github.com/ReversecLabs/drozer)
[mobile security framework](https://github.com/MobSF/Mobile-Security-Framework-MobSF)
[btlejack](https://github.com/virtualabs/btlejack)
[internalblue](https://github.com/seemoo-lab/internalblue/tree/master)
[Intercepter-NG](https://www.youtube.com/watch?app=desktop&v=fF1PVpN_bfE)

https://www.kalilinux.fr/commandes/bettercap-sur-kali-linux/
https://github.com/adafruit/Adafruit_nRF52_nrfutil
