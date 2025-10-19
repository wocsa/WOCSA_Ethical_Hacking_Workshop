import sys
import os

_detected = False  # internal flag

def if_who_am_i_detected() -> bool:
    """Return True if a whoami-like execution has been observed."""
    return bool(_detected)

# --- detection logic: basic os.system hook only ---
_orig_os_system = os.system

def _looks_like_whoami(cmd: str) -> bool:
    if not isinstance(cmd, str):
        return False
    return any(part.strip().endswith("whoami") for part in cmd.strip().split())

def _os_system_wrapper(cmd):
    global _detected
    if _looks_like_whoami(cmd):
        _detected = True
    return _orig_os_system(cmd)

os.system = _os_system_wrapper  # patch it on import

