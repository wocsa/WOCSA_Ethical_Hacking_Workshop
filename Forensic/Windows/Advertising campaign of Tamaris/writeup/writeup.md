# WRITE-UP

## Warning
Attention, some files may be malicious. It is recommended to run the workshop from an isolated virtual machine disconnected from the rest of the network.

## Incident Overview
An employee of a company downloaded a document and executed it on their workstation. After execution, they realized that the file did not perform the expected action: displaying the new marketing campaign. They contacted the company's security service to report the issue. You are required to analyze the workstation to understand what happened on the computer since the file was downloaded.

## Identifying Entry Point / Confirming User Claims
The goal is not to trace the entire file access hierarchy but to determine the last source it came from: USB drive/shared folder/website, for instance.

### Interesting Artifact:
- The ADS stream of the downloaded file: ads.txt

### Analysis:
- ZoneId=3 field corresponds to the "Internet" zone defined by the ADS structure.
- This means the file was downloaded from the Internet (not from a shared folder or USB drive).
- The referer URL is found with the following URL:
  - hxxps://8000n.uqin[.]ru/h086/aHR0cHM6Ly84MDAwbi51cWluL/index.html
- The user accessed this page before downloading the file.
- Next, the HostUrl field is found:
  - hxxps://8000n.uqin[.]ru/h086/aHR0cHM6Ly84MDAwbi51cWluL/campagne_marketing.zip
- The HostUrl field contains the exact URL for downloading the file. It points to the campagne_marketing.zip file hosted on the site.

### Analysis Conclusion:
- File downloaded from the Internet
- User's claims consistent with explanation
- Entry point: suspicious file downloaded from the Internet

## Understanding Actions with the Downloaded ZIP File
The user unzipped the archive and executed the file.

### Objective:
- To know what is inside the ZIP
- To know what was launched by the user

### Interesting Artifact:
- MFT (Master File Table)

### Analysis:
- Open the CSV file with the parsed MFT.
- Remove lines related to ADS that can disrupt parsing (a few lines).
- ***Hint: Sort by descending order of file creation dates***
- Look for a downloaded ZIP file.
  - Filter by extension column to find ".zip".
  - Note the file creation date if needed to know the incident date later.
- The file is named "bf40c0a022c8ccfe8115b3d61a9f50299994e4dfd267377251fdf56f109d3f22.zip" and was created on 2024-02-11 03:47:45.4016167.
- Look from this date for events related to files to determine the creation or modification of other files.
- 23 seconds later, a MFT entry is found with the creation of a folder named after the ZIP archive.
- Just after, creation of a subfolder and a folder named "Advertising campaign of Tamaris 1".
- Events after this date can be searched for traces of program execution, notably through the creation of Prefetch files indicating program execution.
- A file creation in the Windows\Prefetch directory is found earlier.
  - Prefetch file created: CHXSMARTSCREEN.EXE-614CE6FF.pf
- This indicates that an executable chxsmartscreen.exe was launched by the user.

### Interesting Artifact:
- Prefetch

### Analysis:
This can be analyzed with a tool like Eric Zimmerman's PECmd.exe or similar on Linux (sccainfo).
The execution date of the file confirms from a second source that the executable was launched only once.

### Back to the MFT
- Just after the creation of the prefetch, a folder named "MicrosoftGreenxtFG" is created in the AppData\Local directory.
- Many Python files are created in a subfolder named Lib with more or less suspicious names: email
- At the root, a file named "python.exe" is created.
- A file named "libb1.py" is found at the root.
- Subsequently, PYC files are created, indicating compilation following script interpretation by Python, potentially implying Python execution.
- File creation in the Temp directory is observed, which is often used by malware to store files.
- Several files are created within the recently created directory "FR bat_1209-tamaris-DI2 19h49m37s-10-2-2024", including "Screenshot.png", "Web Data", "Login Data", "Cookies", "Profile1", "Chrome", "Pass.txt", and "Secret key".
- A ZIP file of this directory is generated in Temp.
- Creation of a file id.txt.
- Creation of a PREFETCH PYTHON.EXE-69DC0DFE.pf file.

### Interesting Artifact:
- Prefetch of Python

### Analysis:
In the files related to the Python executable, links are found with the files created and observed above:
- Pass.txt
- Secret key
- Cookie
- Login data...
- And the ZIP archive

### Conclusion:
Python was executed and generated these files.

## Hypothesis
- A file was executed by the user.
- Creation of a directory in AppData\Local.
- Installation of Python.
- Execution of Python.
- Python generates suspicious files.
- A ZIP archive is created.
Examining the file naming convention, it appears to correspond to the directory structure of an info-stealer's log directory, which is exfiltrated by the attacker (via the creation of a ZIP archive at the end).

## Detection of Data Exfiltration
- Analysis of network traffic:
  - Firewall
  - Application telemetry
- Detection of a directory containing filterable elements (e.g., ZIP/RAR archive)
- Through the MFT, an archive containing sensitive elements (pass.txt, secret key) is observed.
- We will use the SRUM database seen in the theoretical part.

### SRUM Analysis
- Tools: SrumECmd by Eric for parsing into CSV

### Analysis:
- Examine the export on the network section (NetworkUsages).
- Search for an executable related to the suspicious elements observed: microsoftgreen (seen in AppData\Local) or even the Python.exe executable.
- Python.exe is found as the executable.
- The timestamp indicated corresponds to this execution (03:52).
- BytesReceived & BytesSent columns are observed:
  - Received ==> receiving execution information (reverse shell)
  - BytesSent ==> sending data externally
- 70kb received.
- 741kb (0.7mb) sent.
Questions arise:
- Does the size of the ZIP archive created by Python correspond to the volume sent over the Internet?
- Unfortunately, SRUM cannot provide more details, so this remains a hypothesis.
- According to the MFT, the archive weighs 637kb (0.63mb).

## Malware Analysis

It was observed that there was a PY file "libb1.py" at the root of MicrosoftGreen next to the python.exe executable that was created. This may be the executed malware.
Upon opening it, we see Python code deobfuscated, allowing us to understand the various actions.

### Analysis:
- Numerous libraries imported, coherent with the list of files created by the malware in AppData\Local\MicrosoftGreen\lib
- Variables with keywords "api" and "bot"
- IDs
- Retrieval of username and hostname through the "os" library.
- Retrieval of OS version, IP
- Functions for retrieving Chrome data
- Killing Chrome in case of execution.
- Copying cookies/web data/login.
- The same process for Edge/Brave/Chromium/Firefox.
- Information on tokens for accessing Facebook ad campaigns.
- In the main() function, configuration for sending data to Telegram, including sending the ZIP archive observed earlier.

By searching for certain keywords (line 348) in a foreign language on Google, we find an analysis article of malware (https://medium.com/@ic3_2k/an%C3%A1lisis-de-malware-en-python-servido-desde-gitlab-com-c90741e130a1) with similar functionality.

## Conclusion
The analysis reveals a sophisticated attack involving the execution of a malicious file, installation of Python, execution of Python scripts leading to data exfiltration, and potential surveillance through various web browsers. This underscores the importance of robust cybersecurity measures and user vigilance to prevent and detect such incidents.