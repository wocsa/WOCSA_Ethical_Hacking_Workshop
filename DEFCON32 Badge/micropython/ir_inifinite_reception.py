from machine import Pin, UART
import time

# Configuration
baud_rate = 9600
bit_time = 1 / baud_rate
pulse_duration = bit_time * 3 / 16

# Pin Definitions
IR_RX_PIN = 27  # Pin connected to RXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)

# Confirmation byte
CONFIRM_BYTE = 0xA3

# Setup UART (for RXD)
uart = UART(1, baudrate=baud_rate, bits=8, parity=None, stop=1, rx=Pin(IR_RX_PIN))

# Setup IRDA shutdown control pin
irda_sd = Pin(IRDA_SD_PIN, Pin.OUT)

# Ensure the IRDA transceiver is enabled (set SD pin low)
irda_sd.off()

# IR Receiver Pin (for receiving bits)
ir_receiver = Pin(IR_RX_PIN, Pin.IN)

def receive_bit():
    # Wait for start bit
    while ir_receiver.value() == 1:
        pass
    time.sleep(bit_time / 2)  # Wait until the middle of the bit time

    # Read bit value
    pulse = ir_receiver.value()
    if pulse == 1:
        time.sleep(pulse_duration)
        return 1
    else:
        time.sleep(bit_time - pulse_duration)
        return 0

def receive_byte():
    byte = 0
    # Wait for start bit (0)
    while receive_bit() != 0:
        pass
    # Read 8 bits of data (LSB first)
    for i in range(8):
        bit = receive_bit()
        byte |= (bit << i)
    # Wait for stop bit (1)
    while receive_bit() != 1:
        pass
    return byte

def receive_data(num_bytes):
    # Wait for confirmation byte first
    received_confirm = receive_byte()
#     if received_confirm != CONFIRM_BYTE:
#         print("Error: Confirmation byte not received or incorrect.")
#         return None
    
    received = []
    for _ in range(num_bytes):
        received.append(receive_byte())
    return bytes(received)

# Example usage

# Ensure the IRDA transceiver is enabled
irda_sd.off()

# Simulate some delay (time for the message to be sent and received)
time.sleep(2)

# Receive Message
print("Waiting to receive message...")
message_length = 13  # Length of "Hello, World!"
received_message = receive_data(message_length)
if received_message:
    print("Received message:", received_message)
else:
    print("Failed to receive the correct confirmation byte.")

# Optionally disable the IRDA transceiver (put it in shutdown mode)
irda_sd.on()
