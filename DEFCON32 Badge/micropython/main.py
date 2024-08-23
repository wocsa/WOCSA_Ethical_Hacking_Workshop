from machine import Pin, SoftSPI
import st7789
import neopixel
import time
from random import randint
import vga2_16x16 as font
from irda_sir import IrDASIR
#from irda_simple import SimpleIrDA

# Pin definitions based on the schematic and ZHX1010 datasheet
IR_RX_PIN = 27  # Pin connected to RXD of ZHX1010
IR_TX_PIN = 26  # Pin connected to TXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)
BUTTON_LEFT_PIN = 19  # Pin for SW_LEFT
BUTTON_UP_PIN = 18  # Pin for SW_UP
BUTTON_DOWN_PIN = 17  # Pin for SW_DOWN
BUTTON_RIGHT_PIN = 16  # Pin for SW_RIGHT
BUTTON_START_PIN = 22  # Pin for SW_START
BUTTON_SELECT_PIN = 23  # Pin for SW_SELECT
BUTTON_A_PIN = 21  # Pin for SW_A
BUTTON_B_PIN = 20  # Pin for SW_B

# Initialize buttons
btn_up = Pin(BUTTON_UP_PIN, Pin.IN, Pin.PULL_UP)
btn_down = Pin(BUTTON_DOWN_PIN, Pin.IN, Pin.PULL_UP)
btn_select = Pin(BUTTON_SELECT_PIN, Pin.IN, Pin.PULL_UP)
btn_a = Pin(BUTTON_A_PIN, Pin.IN, Pin.PULL_UP)
btn_b = Pin(BUTTON_B_PIN, Pin.IN, Pin.PULL_UP)

#Initialize IRDA switch
irda_switch = Pin(IRDA_SD_PIN,Pin.OUT)

# SPI pins for the display (based on common DEFCON badge setups)
SPI_SCK_PIN = 8
SPI_MOSI_PIN = 6
DISPLAY_CS_PIN = 9
DISPLAY_DC_PIN = 5
DISPLAY_BL_PIN = 10  # If you have backlight control

# Timing configuration for Slow IrDA
baud_rate = 9600
bit_time = 1 / baud_rate
pulse_duration = bit_time * 3 / 16

# Confirmation byte
CONFIRM_BYTE = 0xA3

# List of messages for IR brute force
bruteforce_list = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]

# Variable to store received IR data
stored_ir_signal = b''

# Buffer for sending data
send_buffer = []
tx_busy = False  # TX status flag

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



def calculate_checksum(item_number):
    """Calculate checksum as per Slow IrDA compliance."""
    return ((item_number - 15) * 16 + item_number) & 0xFF

def ir_bruteforce():
    # Initialize the IrDA SIR object
    irda = IrDASIR(tx_pin=IR_TX_PIN, rx_pin=IR_TX_PIN)
    irda_switch.off() #disable to switch on IRDA
    # Infinite loop to send random uint8 numbers between 1 and 13
    while btn_b.value():
        # Generate a random uint8 number between 1 and 13
        random_number = randint(1, 13)
        
        # Calculate the checksum for the random number
        checksum = calculate_checksum(random_number)
        
        # Print the random number and checksum being sent
        print(f"Sending random number: {random_number}, Checksum: {checksum}")
        display.text(font, f"Sending n:{random_number} c:{checksum}", 10, 205, st7789.RED) 
        
        # Send the random number checksum
        irda.send(str(checksum))
        
        # Wait for a short period before sending the next number
        time.sleep(1)  # Adjust the delay as needed between sending messages
   


def display_menu(selection):
    # Removed display.fill(st7789.BLACK) as requested
    if selection == 0:
        display.text(font, "-> Read & Store IR Signal", 10, 105)
        display.text(font, "   Send Stored IR Signal", 10, 140)
        display.text(font, "   Rainbow LED Cycle", 10, 175)
        display.text(font, "   IR Bruteforce  ", 10, 205)
    elif selection == 1:
        display.text(font, "   Read & Store IR Signal", 10, 105)
        display.text(font, "-> Send Stored IR Signal", 10, 140)
        display.text(font, "   Rainbow LED Cycle", 10, 175)
        display.text(font, "   IR Bruteforce  ", 10, 205)
    elif selection == 2:
        display.text(font, "   Read & Store IR Signal", 10, 105)
        display.text(font, "   Send Stored IR Signal", 10, 140)
        display.text(font, "-> Rainbow LED Cycle", 10, 175)
        display.text(font, "   IR Bruteforce  ", 10, 205)
    elif selection == 3:
        display.text(font, "   Read & Store IR Signal", 10, 105)
        display.text(font, "   Send Stored IR Signal", 10, 140)
        display.text(font, "   Rainbow LED Cycle", 10, 175)
        display.text(font, "-> IR Bruteforce  ", 10, 205)        

def main():
    irda_switch.off() #enable IrDA
    # Initialize the IrDA SIR object
    irda = IrDASIR(tx_pin=IR_TX_PIN, rx_pin=IR_RX_PIN)
    # Initialize the SimpleIrDA object
    #irda = SimpleIrDA(tx_pin=IR_TX_PIN, rx_pin=IR_RX_PIN)
    
    
    # Store IR received data
    global all_data
    
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
            menu_selection = (menu_selection - 1) % 4  # Update to cycle through 4 options
            display_menu(menu_selection)
            time.sleep(0.2)  # Debounce delay

        if not btn_down.value():  # Button pressed
            menu_selection = (menu_selection + 1) % 4  # Update to cycle through 4 options
            display_menu(menu_selection)
            time.sleep(0.2)  # Debounce delay

        if not btn_a.value():  # Button pressed
            if menu_selection == 0:
                display.text(font, "-> Waiting IR Data...", 10, 105, st7789.BLUE)
                all_data=irda.receive(timeout=5000)
                if all_data:
                    display.text(font, "-> Received Data", 10, 105, st7789.GREEN)
                else:
                    display.text(font, "-> No Data Received", 10, 105, st7789.RED)
                print(all_data.decode('ascii'))
                irda_switch.on() #enable to switch on IRDA
            elif menu_selection == 1:
                if all_data:
                    irda_switch.off() #disable to switch on IRDA
                    display.text(font, f"-> Sending stored data", 10, 140,st7789.BLUE)
                    irda.send(all_data)
                    display.text(font, "-> IR Data Sent", 10, 140,st7789.GREEN)
                    irda_switch.on() #enable to switch on IRDA
                else:
                    display.text(font, "-> No Data Stored", 10, 140,st7789.RED)
            elif menu_selection == 2:
                rainbow_cycle(0.2)  # Activate Rainbow LED Cycle
            elif menu_selection == 3:
                ir_bruteforce() 
            display_menu(menu_selection)
            time.sleep(0.5)  # Debounce delay

if __name__ == "__main__":
    main()
