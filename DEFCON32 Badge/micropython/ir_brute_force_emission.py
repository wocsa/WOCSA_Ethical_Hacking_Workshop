from machine import Pin
import time
from irda_sir import IrdaSIR, UartCfg

BUTTON_B_PIN = 20  # Pin for SW_B

# Initialize IrDA in TX mode
def irdaInitTx():
    cfg = UartCfg(
        baudrate=9600,
        char_bits=8,
        stop_bits=1,
        par_ena=False,
        par_even=False,
        rx_en=False,
        tx_en=True
    )
    
    print("Initializing IrDA in TX mode...")
    irda = IrdaSIR(cfg, tx_pin=26, rx_pin=27, sd_pin=7)
    
    # Clear the SD pin to enable IR
    irda.sd_pin.off()

    return irda

# Function to send data
def emit_data(irda, data):
    print(f"Emitting data: {data}")
    irda.send_data(data)
    
    # Wait for TX to complete
    while not irda.check_status()[0]:  # Check TX FIFO empty
        time.sleep(0.01)
    
    print("Data emission complete.")

# Function to continuously send IR signal until the button is pressed
def send_ir_signal(irda, data, button_pin):
    button = Pin(button_pin, Pin.IN, Pin.PULL_UP)  # Initialize the button pin
    
    while True:
        # Check if the button is pressed (assuming active-low)
        if not button.value():
            print("Button pressed. Stopping transmission.")
            break
        
        # Emit the IR signal with the provided data
        emit_data(irda, data)
        
        # Small delay between transmissions
        time.sleep(1)

# Main execution
if __name__ == "__main__":
    # Initialize IrDA in transmit mode
    irda = irdaInitTx()
    
    # Data to be sent via IR
    data_to_send = [0xFC, 0xFF, 0xFD, 0x44, 0x47]
    
    # Call the send_ir_signal function to continuously send the specified IR data
    send_ir_signal(irda, data_to_send, BUTTON_B_PIN)
    
    print("IR signal transmission stopped.")
