from machine import UART, Pin
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)

# Initialize IRDA shutdown pin
irda_sd = Pin(IRDA_SD_PIN, Pin.IN)

uart = UART(1, baudrate=9600, tx=26, rx=27)

while True:
    if uart.any():
        received_byte = uart.read(1)
        if received_byte:
            print(f"Received: {received_byte}")
