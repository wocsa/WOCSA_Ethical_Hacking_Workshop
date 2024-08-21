from machine import Pin, UART
import time

# Configuration
baud_rate = 9600
bit_time = 1 / baud_rate
pulse_duration = bit_time * 3 / 16

# Pin Definitions
IR_RX_PIN = 27  # Pin connected to RXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)

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

def continuous_receive():
    print("Starting continuous reception...")
    while True:
        received_byte = receive_byte()
        print(f"Received byte: {received_byte:02X}")  # Print received byte in hex format

# Ensure the IRDA transceiver is enabled
irda_sd.off()

# Start continuous reception
continuous_receive()

# Optionally disable the IRDA transceiver (put it in shutdown mode)
# irda_sd.on()  # Uncomment this line if you want to manually stop the reception and disable the IR transceiver
