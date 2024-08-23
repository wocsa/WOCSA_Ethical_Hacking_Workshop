from machine import Pin
import time
from irda_sir import IrdaSIR, UartCfg

# Global variables
mIrRxBuf = []
mIrRxWritePos = 0
mIrRxBytesUsed = 0
mIrRxHadOverrun = False
mEEpos = 0

# Predefined match sequence
match = [0xFC, 0xFF, 0xFD, 0x44, 0x47]

# Reception handler (equivalent to myIrdaSIRuartRxF)
def myIrdaSIRuartRxF(rawBuf):
    global mIrRxBuf, mIrRxWritePos, mIrRxBytesUsed, mIrRxHadOverrun, mEEpos

    for val in rawBuf:
        # Buffer the received data
        if mIrRxBytesUsed == len(mIrRxBuf):
            mIrRxHadOverrun = True
            break

        if mIrRxWritePos >= len(mIrRxBuf):
            mIrRxWritePos = 0

        mIrRxBuf.append(val)
        mIrRxWritePos += 1
        mIrRxBytesUsed += 1

    if mEEpos < 0:
        return

    for val in rawBuf:
        if val >> 8:  # Error causes a reset
            mEEpos = 0
            continue

        if val != match[mEEpos] and val != match[mEEpos]:
            mEEpos = 0
            continue

        if mEEpos != len(match) - 1:  # Match advances
            mEEpos += 1
        else:
            mEEpos = -1
            break

# Initialize IrDA in RX mode
def irdaInitRx():
    cfg = UartCfg(
        baudrate=9600,
        char_bits=8,
        stop_bits=1,
        par_ena=False,
        par_even=False,
        rx_en=True,
        tx_en=False
    )
    
    print("Initializing IrDA in RX mode...")
    irda = IrdaSIR(cfg, tx_pin=26, rx_pin=27, sd_pin=7)
    
    # Clear the SD pin to enable IR
    irda.sd_pin.off()

    return irda

# Function to receive data, analogous to emit_data
def receive_data(irda):
    received_data = []
    
    # Receive until no more data is available
    while True:
        data = irda.receive_data()
        if data is None:
            break
        received_data.append(data)
        print(f"Received data: {data}")
    
    # Process the received data with the custom handler
    if received_data:
        myIrdaSIRuartRxF(received_data)

# Main execution
if __name__ == "__main__":
    # Initialize the reception buffer
    mIrRxBuf = []

    # Initialize IrDA in receive mode
    irda = irdaInitRx()

    # Continuously receive data
    while True:
        receive_data(irda)
        
        # Check for buffer overrun
        if mIrRxHadOverrun:
            print("Buffer overrun occurred!")
            mIrRxHadOverrun = False  # Reset overrun flag
        
        time.sleep(1)  # Small delay to prevent overwhelming the loop
