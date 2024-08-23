from machine import Pin
import rp2
import time

class SimpleIrDA:
    def __init__(self, tx_pin, rx_pin):
        self.tx_pin = tx_pin
        self.rx_pin = rx_pin
        
        # Try slightly adjusting the frequency
        self.sm_tx = rp2.StateMachine(0, self.ir_tx, freq=960000, out_base=Pin(tx_pin))  # Slightly less than 1 MHz
        self.sm_rx = rp2.StateMachine(1, self.ir_rx, freq=960000, in_base=Pin(rx_pin))
        
        self.sm_tx.active(1)
        self.sm_rx.active(1)

    @rp2.asm_pio(out_init=rp2.PIO.OUT_HIGH, autopull=False)
    def ir_tx():
        wrap_target()
        pull(noblock)
        mov(y, osr)
        set(pins, 1)
        mov(x, y)
        jmp(x_dec, "x_dec_high")
        set(pins, 0)
        jmp("next_pulse")

        label("x_dec_high")
        jmp(x_dec, "x_dec_high")

        label("next_pulse")
        pull(noblock)
        mov(y, osr)
        mov(x, y)
        jmp(x_dec, "x_dec_low")

        label("x_dec_low")
        jmp(x_dec, "x_dec_low")
        wrap()

    @rp2.asm_pio(in_shiftdir=rp2.PIO.SHIFT_RIGHT, autopush=True, push_thresh=32)
    def ir_rx():
        wrap_target()
        wait(1, pin, 0)               # Wait for signal to go high
        mov(x, invert(null))          # Reset x register
        label("count_high")
        jmp(pin, "count_high") [1]    # Count duration while pin is high
        mov(isr, x)
        push()

        mov(x, invert(null))
        label("count_low")
        jmp(pin, "got_signal") [1]    # Count duration while pin is low
        jmp("count_low")

        label("got_signal")
        mov(isr, x)
        push()
        wrap()

    def receive(self, timeout=5000):
        start_time = time.ticks_ms()
        raw_signal = []

        while True:
            if self.sm_rx.rx_fifo() > 0:
                duration = self.sm_rx.get()
                if duration > 0 and duration < 65535:  # Filter out zero and extremely long durations
                    raw_signal.append(duration)
                start_time = time.ticks_ms()  # Reset timeout when signal is received
            
            if time.ticks_diff(time.ticks_ms(), start_time) > timeout:
                break

        return raw_signal

    def send(self, raw_signal):
        for duration in raw_signal:
            self.sm_tx.put(duration)

    def close(self):
        self.sm_tx.active(0)
        self.sm_rx.active(0)

# Example usage:
# irda = SimpleIrDA(tx_pin=16, rx_pin=17)
# received_signal = irda.receive(timeout=5000)
# print(received_signal)
# irda.send(received_signal)
# irda.close()
