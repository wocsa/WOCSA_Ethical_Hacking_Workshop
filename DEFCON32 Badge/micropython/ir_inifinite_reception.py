from machine import Pin
import time


# Configuration
baud_rate = 9600
bit_time = 1 / baud_rate
pulse_duration = bit_time * 3 / 16
# Pin Definitions
IR_RX_PIN = 27  # Pin connected to RXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)

# Setup IRDA shutdown control pin
irda_sd = Pin(IRDA_SD_PIN, Pin.OUT)

# Ensure the IRDA transceiver is enabled (set SD pin low)
irda_sd.off()

# IR Receiver Pin (for receiving bits)
ir_receiver = Pin(IR_RX_PIN, Pin.IN)

def receive_bit(timeout=0.1):
    start_time = time.time()
    # Wait for start bit
    while ir_receiver.value() == 1:
        if time.time() - start_time > timeout:
            return None  # Return None if timeout occurs while waiting for a bit
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
    start_bit = receive_bit()
    if start_bit is None:
        return None
    if start_bit != 0:
        return None
    # Read 8 bits of data (LSB first)
    for i in range(8):
        bit = receive_bit()
        if bit is None:
            return None
        byte |= (bit << i)
    # Wait for stop bit (1)
    stop_bit = receive_bit()
    if stop_bit is None or stop_bit != 1:
        return None
    return byte

def receive_data(timeout=5):
    received = []
    last_received_time = time.time()

    while True:
        byte = receive_byte()
        if byte is None:
            # Check if timeout has been reached
            if time.time() - last_received_time > timeout:
                print("Timeout reached, stopping reception.")
                break
        else:
            received.append(byte)
            last_received_time = time.time()
            print(f"Received byte: {byte:02X}")  # Print the byte in hexadecimal format

    return bytes(received)

# Example usage

# Ensure the IRDA transceiver is enabled
irda_sd.off()

# Simulate some delay (time for the message to be sent and received)
time.sleep(2)

# Receive Message
print("Waiting to receive message...")
received_message = receive_data()
if received_message:
    print("Complete message received:", received_message)
else:
    print("Failed to receive the correct confirmation byte.")

# Optionally disable the IRDA transceiver (put it in shutdown mode)
irda_sd.on()
