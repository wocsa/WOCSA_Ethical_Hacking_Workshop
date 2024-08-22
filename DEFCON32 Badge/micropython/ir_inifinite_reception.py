from rp2 import PIO, StateMachine, asm_pio
from machine import Pin

# Setup IR_SD pin
ir_sd_pin = Pin(17, Pin.OUT)
ir_sd_pin.value(0)  # Set to 0 to enable the transceiver

@asm_pio(set_init=PIO.IN_HIGH)
def irda_rx():
    # Wait for the start bit (logic low)
    wait(0, pin, 0)
    # Delay to reach the middle of the first data bit
    set(x, 7) [7]
    label("bitloop")
    in_(pins, 1)    # Shift in the next data bit
    jmp(x_dec, "bitloop")  # Loop 8 times for 8 bits

# Setup the state machine on GPIO pin connected to RXD
sm = StateMachine(0, irda_rx, freq=9600 * 8, in_base=Pin(16))

sm.active(1)

while True:
    if sm.rx_fifo():
        data = sm.get() & 0xff  # Get one byte of data
        print(chr(data))  # Print the received character
        
    # Optionally, toggle the IR_SD pin based on some condition
    # For example, disable the transceiver after a period of inactivity
    # ir_sd_pin.value(1)  # Set to 1 to disable the transceiver
