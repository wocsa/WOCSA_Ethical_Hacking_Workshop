from machine import Pin, UART
import time
from random import randint

# Define UART for IR communication
IR_RX_PIN = 27  # Pin connected to RXD of ZHX1010
IR_TX_PIN = 26  # Pin connected to TXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)
uart = UART(1, baudrate=9600, bits=3, parity=None, stop=1, tx=IR_TX_PIN, rx=IR_RX_PIN)

# Initialize IRDA shutdown pin
irda_sd = Pin(IRDA_SD_PIN, Pin.OUT)

def send_test_signal():
    irda_sd.off()  # Enable ZHX1010 to transmit
    test_message = b'Hello IR!'  # Example test message to send via IR

    

    uart.write(test_message)  # Send the test message via UART
    
    print(f"Sending IR signal {test_message}")

    irda_sd.on()  # Shutdown ZHX1010 after sending
    print("IR signal sent.")

# Test the IR transmission
while True:
    send_test_signal()
