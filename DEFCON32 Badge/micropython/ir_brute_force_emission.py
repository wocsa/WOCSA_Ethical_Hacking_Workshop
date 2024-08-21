from machine import Pin, UART
import time

# Configuration
baud_rate = 9600
bit_time = 1 / baud_rate
pulse_duration = bit_time * 3 / 16

# Pin Definitions
IR_TX_PIN = 26  # Pin connected to TXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)

# Confirmation byte
CONFIRM_BYTE = 0xA3

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
    # Send confirmation byte first
    send_byte(CONFIRM_BYTE)
    # Send the actual data
    for byte in data:
        send_byte(byte)

# Example usage

# Ensure the IRDA transceiver is enabled
irda_sd.off()

# Send Message
message_to_send = b"Hello, World!"
print("Sending message:", message_to_send)
send_data(message_to_send)

# Optionally disable the IRDA transceiver (put it in shutdown mode)
irda_sd.on()
