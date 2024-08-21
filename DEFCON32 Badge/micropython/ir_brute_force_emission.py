from machine import Pin, UART
import time
from random import randint

# Configuration
baud_rate = 9600
bit_time = 1 / baud_rate
pulse_duration = bit_time * 3 / 16

# Pin Definitions
IR_TX_PIN = 26  # Pin connected to TXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)

# Setup UART (for TXD)
uart = UART(1, baudrate=baud_rate, bits=8, parity=None, stop=1, tx=Pin(IR_TX_PIN))

# Setup IRDA shutdown control pin
irda_sd = Pin(IRDA_SD_PIN, Pin.OUT)

# Ensure the IRDA transceiver is enabled (set SD pin low)
irda_sd.off()

# IR LED Pin (for transmitting bits)
ir_led = Pin(IR_TX_PIN, Pin.OUT)

def send_bit(bit):
    if bit == 1:
        ir_led.on()
        time.sleep(pulse_duration)
        ir_led.off()
        time.sleep(bit_time - pulse_duration)
    else:
        ir_led.off()
        time.sleep(bit_time)

def send_byte(byte):
    # Start bit (always 0)
    send_bit(0)
    # Data bits (LSB first)
    for i in range(8):
        send_bit((byte >> i) & 1)
    # Stop bit (always 1)
    send_bit(1)

def send_data(data):
    # Send the actual data (e.g., the checksum)
    for byte in data:
        send_byte(byte)

def calculate_checksum(item_number):
    """Calculate checksum as per Slow IrDA compliance."""
    return ((item_number - 15) * 16 + item_number) & 0xFF

# Infinite loop to send random uint8 numbers between 1 and 13
while True:
    # Generate a random uint8 number between 1 and 13
    random_number = randint(1, 13)
    
    # Calculate the checksum for the random number
    checksum = calculate_checksum(random_number)
    
    # Print the random number and checksum being sent
    print(f"Sending random number: {random_number}, Checksum: {checksum}")
    
    # Send the random number followed by the checksum
    send_data([random_number, checksum])
    
    # Wait for a short period before sending the next number
    time.sleep(5)  # Adjust the delay as needed between sending messages

# Optionally disable the IRDA transceiver (put it in shutdown mode)
# irda_sd.on()  # Uncomment this line if you want to manually stop and disable the IR transceiver
