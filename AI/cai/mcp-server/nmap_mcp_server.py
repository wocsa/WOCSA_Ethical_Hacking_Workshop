import argparse
import nmap
from fastmcp import FastMCP
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger('Nmap-MCP-Server')

# Initialize the MCP server with a name
mcp = FastMCP("Nmap MCP Server")
nm = nmap.PortScanner()

def execute_scan(target: str, args: str = "") -> dict:
    """
    Execute an Nmap scan using the specified arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The scan results.
    """
    return nm.scan(hosts=target, arguments=args)

@mcp.tool()
def scan_top_ports(target: str, args: str = "") -> dict:
    """
    Scan the top ports of the specified target with optional custom arguments.

    Args:
        target (str): The target hostname or IP address.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The scan results in JSON format.
    """
    return execute_scan(target, args)

@mcp.tool()
def dns_brute_force(target: str, args: str = "") -> dict:
    """
    Perform DNS brute-force to discover subdomains of the specified target.

    Args:
        target (str): The target domain to scan.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The DNS brute-force scan results.
    """
    return execute_scan(target, f"--script dns-brute {args}")

@mcp.tool()
def list_scan(target: str, args: str = "") -> dict:
    """
    Perform a list scan on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The list scan results.
    """
    return execute_scan(target, f"-sL {args}")

@mcp.tool()
def os_detection(target: str, args: str = "") -> dict:
    """
    Perform OS detection on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The OS detection results.
    """
    return execute_scan(target, f"-O {args}")

@mcp.tool()
def version_detection(target: str, args: str = "") -> dict:
    """
    Detect service versions on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The version detection results.
    """
    return execute_scan(target, f"-sV {args}")

@mcp.tool()
def fin_scan(target: str, args: str = "") -> dict:
    """
    Perform a FIN scan on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The FIN scan results.
    """
    return execute_scan(target, f"-sF {args}")

@mcp.tool()
def idle_scan(target: str, args: str = "") -> dict:
    """
    Perform an idle scan on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The idle scan results.
    """
    return execute_scan(target, f"-sI {args}")

@mcp.tool()
def ping_scan(target: str, args: str = "") -> dict:
    """
    Perform a ping scan on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The ping scan results.
    """
    return execute_scan(target, f"-sn {args}")

@mcp.tool()
def syn_scan(target: str, args: str = "") -> dict:
    """
    Perform a SYN scan on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The SYN scan results.
    """
    return execute_scan(target, f"-sS {args}")

@mcp.tool()
def tcp_scan(target: str, args: str = "") -> dict:
    """
    Perform a TCP connect scan on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The TCP scan results.
    """
    return execute_scan(target, f"-sT {args}")

@mcp.tool()
def udp_scan(target: str, args: str = "") -> dict:
    """
    Perform a UDP scan on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The UDP scan results.
    """
    return execute_scan(target, f"-sU {args}")

@mcp.tool()
def portscan_only(target: str, args: str = "") -> dict:
    """
    Perform a port scan only on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The port scan results.
    """
    return execute_scan(target, f"-sP {args}")

@mcp.tool()
def no_portscan(target: str, args: str = "") -> dict:
    """
    Perform host discovery without port scanning on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The host discovery results.
    """
    return execute_scan(target, f"-sn {args}")

@mcp.tool()
def arp_discovery(target: str, args: str = "") -> dict:
    """
    Perform ARP discovery on the specified target with optional custom arguments.

    Args:
        target (str): The target IP address or subnet (e.g., '192.168.1.0/24').
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The ARP discovery results.
    """
    return execute_scan(target, f"-PR {args}")

@mcp.tool()
def disable_dns_resolution(target: str, args: str = "") -> dict:
    """
    Perform a scan on the specified target with DNS resolution disabled and optional custom arguments.

    Args:
        target (str): The target IP address or hostname.
        args (str): Additional Nmap command-line arguments.

    Returns:
        dict: The scan results with DNS resolution disabled.
    """
    return execute_scan(target, f"-n {args}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Run the Nmap MCP Server")
    parser.add_argument("--host", type=str, default="localhost", help="Host for the MCP SSE server")
    parser.add_argument("--port", type=int, default=8000, help="Port for the MCP SSE server")
    args = parser.parse_args()

    # Log the registered tools
    logger.info(f"Registered tools: {mcp.get_tools()}")

    # Use SSETransport to specify host and port
    mcp.run(transport="sse", host=args.host, port=args.port)
