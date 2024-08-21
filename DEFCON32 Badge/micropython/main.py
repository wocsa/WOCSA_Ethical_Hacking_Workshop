from machine import Pin, UART, SoftSPI
import st7789
import neopixel
import time
from random import randint
import vga2_16x16 as font


# Pin definitions based on the schematic and ZHX1010 datasheet
IR_RX_PIN = 27  # Pin connected to RXD of ZHX1010
IR_TX_PIN = 26  # Pin connected to TXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)
BUTTON_UP_PIN = 18  # Pin for SW_UP
BUTTON_DOWN_PIN = 17  # Pin for SW_DOWN
BUTTON_SELECT_PIN = 23  # Pin for SW_SELECT
BUTTON_A_PIN = 21  # Pin for SW_A
BUTTON_B_PIN = 20  # Pin for SW_B

# SPI pins for the display (based on common DEFCON badge setups)
SPI_SCK_PIN = 8
SPI_MOSI_PIN = 6
DISPLAY_CS_PIN = 9
DISPLAY_DC_PIN = 5
DISPLAY_BL_PIN = 10  # If you have backlight control

# UART setup for IR communication
uart = UART(1, baudrate=9600, bits=3, parity=None, stop=1, tx=IR_TX_PIN, rx=IR_RX_PIN)

# Confirmation byte
CONFIRM_BYTE = b'\xa3'

# list of messages for IR brute force
bruteforce_list = [1,2,3,4,5,6,7,8,9,10,11,12]

# Variable to store received IR data
stored_ir_signal = b''

def config(rotation=0, buffer_size=0, options=0):
    spi = SoftSPI(baudrate=70000000,
                  polarity=0,
                  phase=0,
                  sck=Pin(SPI_SCK_PIN),
                  mosi=Pin(SPI_MOSI_PIN),
                  miso=Pin(28))  # SoftSPI needs a MISO pin, used one of the GPIO on the SAO

    return st7789.ST7789(
        spi,
        240,
        320,
        cs=Pin(DISPLAY_CS_PIN, Pin.OUT),
        dc=Pin(DISPLAY_DC_PIN, Pin.OUT),
        backlight=Pin(DISPLAY_BL_PIN, Pin.OUT),
        rotation=rotation,
        options=options,
        buffer_size=buffer_size,
        color_order=st7789.RGB,
        inversion=False)

display = config(1, buffer_size=4096)  # rotation 3 for normal orientation, 1 for upside down

# Initialize IRDA shutdown pin
irda_sd = Pin(IRDA_SD_PIN, Pin.OUT)

# Initialize buttons
btn_up = Pin(BUTTON_UP_PIN, Pin.IN, Pin.PULL_UP)
btn_down = Pin(BUTTON_DOWN_PIN, Pin.IN, Pin.PULL_UP)
btn_select = Pin(BUTTON_SELECT_PIN, Pin.IN, Pin.PULL_UP)
btn_a = Pin(BUTTON_A_PIN, Pin.IN, Pin.PULL_UP)
btn_b = Pin(BUTTON_B_PIN, Pin.IN, Pin.PULL_UP)

num_pixels = 9
np = neopixel.NeoPixel(Pin(4), num_pixels)

RED = (255, 0, 0)
YELLOW = (255, 150, 0)
GREEN = (0, 255, 0)
CYAN = (0, 255, 255)
BLUE = (0, 0, 255)
PURPLE = (180, 0, 255)
COLORS = [RED, YELLOW, GREEN, CYAN, BLUE, PURPLE]

# Define the `wheel` function to create colors across 0-255 positions.
def wheel(pos):
    if pos < 0 or pos > 255:
        return (0, 0, 0)
    if pos < 85:
        return (255 - pos * 3, pos * 3, 0)
    if pos < 170:
        pos -= 85
        return (0, 255 - pos * 3, pos * 3)
    pos -= 170
    return (pos * 3, 0, 255 - pos * 3)

def rainbow_cycle(wait):
    for j in range(255):
        for i in range(num_pixels):
            rc_index = (i * 256 // num_pixels) + j
            np[i] = wheel(rc_index & 255)
        np.write()
        time.sleep(0.01)

def read_ir_signal():
    global stored_ir_signal
    irda_sd.on()  # Enable ZHX1010 to receive
    display.text(font, "Reading IR Signal...", 10, 105, st7789.WHITE)
    
    received_data = b''
    while True:
        if uart.any():
            received_data += uart.read()
            if received_data:  # If data is received
                uart.write(CONFIRM_BYTE)  # Send confirmation byte
                print(f"Received IR data: {received_data}")
                display.text(font, f"Data: {received_data}", 10, 105, st7789.WHITE)
                print(f"Sent confirmation byte: {CONFIRM_BYTE}")
                break
    
    irda_sd.off()  # Shutdown ZHX1010 to save power
    stored_ir_signal = received_data  # Store the received data
    time.sleep(2)
    display.text(font, "IR Signal Stored", 10, 105, st7789.WHITE)
    time.sleep(2)

def send_ir_signal(message):
    global stored_ir_signal
    irda_sd.off()  # Enable ZHX1010 to transmit
    display.text(font, "IR Signal Sending", 10, 140, st7789.WHITE)
    
    if stored_ir_signal:
        uart.write(stored_ir_signal)  # Send the stored data
        print(f"Sent IR data: {stored_ir_signal}")
    elif message:
        uart.write(message)
        print(f"Sent IR data: {message}")
    else:
        display.text(font, "No IR signal stored.", 10, 140, st7789.RED)
    
    irda_sd.on()  # Shutdown ZHX1010 after sending
    time.sleep(2)

def ir_bruteforce():
    for i in range(1,13):
        str(i).encode('ascii')
        #m = i.to_bytes(1, 'big', False)
        display.text(font, "Sending: "+str(i), 10, 205,st7789.RED)
        send_ir_signal(m)
        while uart.read() != CONFIRM_BYTE:
            send_ir_signal(m)
            display.text(font, "Retry: "+str(i), 10, 205,st7789.RED)
        

def display_menu(selection):
    if selection == 0:
        display.text(font, "-> Read & Store IR Signal", 10, 105)
        display.text(font, "   Send Stored IR Signal", 10, 140)
        display.text(font, "   Rainbow LED Cycle", 10, 175)
        display.text(font, "   IR Bruteforce", 10, 205)
    elif selection == 1:
        display.text(font, "   Read & Store IR Signal", 10, 105)
        display.text(font, "-> Send Stored IR Signal", 10, 140)
        display.text(font, "   Rainbow LED Cycle", 10, 175)
        display.text(font, "   IR Bruteforce", 10, 205)
    elif selection == 2:
        display.text(font, "   Read & Store IR Signal", 10, 105)
        display.text(font, "   Send Stored IR Signal", 10, 140)
        display.text(font, "-> Rainbow LED Cycle", 10, 175)
        display.text(font, "   IR Bruteforce", 10, 205)
    elif selection == 3:
        display.text(font, "   Read & Store IR Signal", 10, 105)
        display.text(font, "   Send Stored IR Signal", 10, 140)
        display.text(font, "   Rainbow LED Cycle", 10, 175)
        display.text(font, "-> IR Bruteforce", 10, 205)        
        
    

def main():
    for i in range(num_pixels):
        np[i] = COLORS[randint(0, 5)]
    np.write()

    display.init()
    display.fill(st7789.BLACK)
    png_file_name = f'wocsa_logo.png'
    display.png(png_file_name, 0, 0)
    time.sleep(2)
    menu_selection = 0
    display_menu(menu_selection)
    
    while True:
        if not btn_up.value():  # Button pressed
            menu_selection = (menu_selection - 1) % 4  # Update to cycle through 3 options
            display_menu(menu_selection)
            time.sleep(0.2)  # Debounce delay

        if not btn_down.value():  # Button pressed
            menu_selection = (menu_selection + 1) % 4  # Update to cycle through 3 options
            display_menu(menu_selection)
            time.sleep(0.2)  # Debounce delay

        if not btn_select.value():  # Button pressed
            if menu_selection == 0:
                read_ir_signal()
            elif menu_selection == 1:
                send_ir_signal()
            elif menu_selection == 2:
                rainbow_cycle(0.2)  # Activate Rainbow LED Cycle
            elif menu_selection == 3:
                ir_bruteforce() 
            display_menu(menu_selection)
            time.sleep(0.5)  # Debounce delay

if __name__ == "__main__":
    main()
