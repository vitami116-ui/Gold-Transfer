import serial
import mysql.connector
import time

# --- CONFIGURATION ---
PORT = 'COM1'
BAUD = 300
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',      # Default XAMPP user
    'password': '',      # Default XAMPP password
    'database': 'milano' 
}

def log_to_db(weight):
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        query = "INSERT INTO scale_logs (weight_value) VALUES (%s)"
        cursor.execute(query, (weight,))
        conn.commit()
        print(f" Saved to Database: {weight}")
    except Exception as e:
        print(f" Database Error: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

# --- MAIN LOOP ---
try:
    ser = serial.Serial(PORT, BAUD, timeout=1)
    print(f"Service Started. Listening to {PORT}...")

    while True:
        if ser.in_waiting > 0:
            raw_data = ser.readline().decode('ascii', errors='ignore').strip()
            if raw_data:
                # Extract number (e.g., 41.80)
                clean_weight = "".join(filter(lambda x: x.isdigit() or x == '.', raw_data))
                
                if clean_weight:
                    print(f"Scale Sent: {clean_weight}")
                    log_to_db(clean_weight)
                    
                    # Also update the text file for the live web display
                    with open("latest_weight.txt", "w") as f:
                        f.write(clean_weight)

        time.sleep(0.1)

except Exception as e:
    print(f"Service Error: {e}")