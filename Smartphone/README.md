# Smartphone Security Workshop

Welcome to the **Smartphone** module of the WOCSA Ethical Hacking Workshop.

This folder is intended for mobile security demonstrations carried out in a controlled and authorised environment. The current focus is a **smartphone-to-smartphone** workshop setup, where one lab device is prepared as the operator platform and another device is used as the test target for demonstrations, analysis, and defensive discussion.

## Related ChatGPT Project

This workshop is linked to the following ChatGPT project:

- [Smartphone Hacking Workshop Project](https://chatgpt.com/g/g-p-684af117e6f481919e9bd0ecef240711-smartphone-hacking-workshop/project)

## Workshop Scope

This module is designed to support workshops around:

- Mobile device security fundamentals
- Android-focused lab preparation
- Wireless and proximity attack surface awareness
- Assessment workflows from a mobile platform
- Safe demonstration of smartphone security risks in a training environment

The material in this directory should be used only on devices, applications, and networks for which you have explicit permission.

## Current Content

### Smartphone vs Smartphone

The main workshop notes are available here:

- [SmartphoneVsSmartphone.md](./SmartphoneVsSmartphone.md)

This document currently covers:

- The attacker smartphone hardware profile
- Nexus 6P preparation notes
- LineageOS and Kali NetHunter installation workflow
- Supporting tools, references, and external resources

### Offensive Assessment Workflow

A lab-safe operator workflow is also available here:

- [OffensiveAssessmentSteps.md](./OffensiveAssessmentSteps.md)

This document focuses on authorised reconnaissance, wireless exposure validation, evidence collection, and defensive reporting from the offensive smartphone.

### Nexus 6P NetHunter Lab Files

The repository also includes a prepared device-specific lab bundle here:

- [nexus6p_lineageos_nethunter/](./nexus6p_lineageos_nethunter/)

This subfolder contains the images and supporting files used for the Nexus 6P + LineageOS + Kali NetHunter setup documented in the workshop notes.

## Suggested Workshop Structure

For GitHub readers, a practical way to present this module is:

1. Introduce the mobile threat model and workshop objectives.
2. Present the lab phones, accessories, and operating assumptions.
3. Prepare the operator device with the required mobile assessment tooling.
4. Demonstrate authorised smartphone-to-smartphone scenarios in an isolated lab.
5. Close with detection, hardening, and legal / ethical boundaries.

## Prerequisites

Depending on the exact scenario, this workshop may require:

- An Android device used as the operator platform
- A separate Android device used as the test target
- ADB and Fastboot on a workstation
- USB cables, OTG adapters, and supported external adapters where needed
- A properly isolated lab environment

## Ethics and Safety

This workshop is for **authorised security testing, awareness, and education only**.

Do not use these techniques against devices, accounts, wireless communications, or applications that you do not own or have explicit written permission to assess. Mobile devices frequently contain highly sensitive personal and professional data, so workshop demonstrations must remain tightly scoped and controlled.

## Contributing

If you extend this module, prefer adding:

- A scenario description
- Lab prerequisites
- Setup instructions
- Demo flow
- Cleanup steps
- Defensive takeaways

That keeps the workshop usable both locally and when browsed directly on GitHub.
