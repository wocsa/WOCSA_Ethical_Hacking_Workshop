import re

def dlp_input_filter(user_prompt: str) -> tuple[bool, str]:
    """
    INPUT FILTER (Red Team Mitigation)
    Analyzes the user's prompt BEFORE it reaches the AI.
    
    Returns:
        - bool: True if the prompt is safe, False if it should be blocked.
        - str: The processed prompt (if True) OR the error message (if False).
    """
    # Default feature: Basic keyword detection for architecture reconnaissance
    if "system prompt" in user_prompt.lower() or "modelfile" in user_prompt.lower():
        return False, "DLP Alert [INPUT]: Request blocked due to suspicious architecture reconnaissance keywords."
        
    return True, user_prompt


def dlp_output_filter(ai_response: str) -> str:
    """
    OUTPUT FILTER (Data Loss Prevention)
    Analyzes the AI's generation BEFORE displaying it to the user.
    
    Returns:
        - str: The sanitized response to be displayed.
    """
    # Default feature: Basic flag detection using Regex
    flag_pattern = r"FLAG-[A-Z0-9\-]+"
    
    if re.search(flag_pattern, ai_response):
        # Redact the detected flag with a security tag
        ai_response = re.sub(flag_pattern, "[REDACTED_BY_PROXY_DLP]", ai_response)
        
    return ai_response