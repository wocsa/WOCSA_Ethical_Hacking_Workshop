from machine import Pin, UART
import time

# Define UART for IR communication
IR_RX_PIN = 27  # Pin connected to RXD of ZHX1010
IR_TX_PIN = 26  # Pin connected to TXD of ZHX1010
IRDA_SD_PIN = 7  # Pin connected to SD of ZHX1010 (Shutdown Control)

# Initialize the IR receiver pin as input
ir_rx = Pin(IR_RX_PIN, Pin.IN)

# Initialize the shutdown pin as output
irda_sd = Pin(IRDA_SD_PIN, Pin.OUT)

# Placeholder to store recorded signal
recorded_signal = []

def check_checksum(checksum):
    CHECKSUM_Y = checksum // 16
    CHECKSUM_X = checksum % 16
    
    # Validate X + Y = 15
    if CHECKSUM_X + CHECKSUM_Y == 15:
        VAR_RECEIVE_ITEM_NUMBER = CHECKSUM_X
        print(f"Valid data received: {VAR_RECEIVE_ITEM_NUMBER}")
        return VAR_RECEIVE_ITEM_NUMBER
    else:
        print(f"Invalid")
        return False

def uart_parser(signal, data_bits=8, parity_bit=None, stop_bits=1):
    frames = []
    i = 0
    while i < len(signal):
        bit, value = signal[i]

        # Look for the start bit (0)
        if bit == 0:
            i += 1
            data = []

            # Collect data bits
            for j in range(data_bits):
                if i < len(signal):
                    data_bit, _ = signal[i]
                    data.append(data_bit)
                    i += 1
                else:
                    break
            
            # Collect parity bit if applicable
            parity = None
            if parity_bit:
                if i < len(signal):
                    parity, _ = signal[i]
                    i += 1
            
            # Collect stop bits and validate
            stop_bit_ok = True
            for _ in range(stop_bits):
                if i < len(signal):
                    stop_bit, _ = signal[i]
                    if stop_bit != 1:
                        stop_bit_ok = False
                    i += 1
                else:
                    stop_bit_ok = False
                    break

            if stop_bit_ok:
                # Convert data bits to a uint8
                byte_value = 0
                for idx, bit in enumerate(data):
                    byte_value |= (bit << idx)
                frames.append(byte_value)

        i += 1

    return frames



def test_ir_sensor():
    # Activate the IR sensor by setting the shutdown pin low
    irda_sd.off()  # Pull the shutdown pin low to enable the sensor
    print("IR sensor activated.")
    time.sleep(1)
    print("Recording IR signal...")
    start_time = time.ticks_us()  # Record start time in microseconds
    wait=True
    while wait==True:  # Adjust the number of samples as needed
        if ir_rx.value()==0:
            while len(recorded_signal) < 5000:
                pulse_duration = time.ticks_diff(time.ticks_us(), start_time)  # Measure pulse duration
                recorded_signal.append((ir_rx.value(), pulse_duration))  # Store the state (high/low) and duration
                start_time = time.ticks_us()  # Reset start time for the next measurement          
            wait=False

# Run the function to test the IR sensor
test_ir_sensor()
binary_output = uart_parser(recorded_signal)
print(f"Binary Output: {binary_output}")
print(f"Record Output: {recorded_signal}")
for item in binary_output:
    print(check_checksum(item))