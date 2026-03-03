from Backend import Database

# Initialize database connection
try:
    db = Database("DataBase/DataBase.db")
    print("Database connection successful")
except Exception as e:
    print(f"Database connection failed: {e}")
    exit()

# Check if tables exist
db.cursor.execute("SELECT name FROM sqlite_master WHERE type='table';")
tables = [table[0] for table in db.cursor.fetchall()]
print("Existing tables:", tables)

# Ensure tables exist before deleting
if "Emails" in tables and "users" in tables:
    try:
        db.cursor.execute("PRAGMA foreign_keys = OFF;")  # Disable foreign key constraints
        db.cursor.execute("DELETE FROM Emails;")
        db.cursor.execute("DELETE FROM users;")
        db.cursor.execute("PRAGMA foreign_keys = ON;")  # Re-enable foreign key constraints
        db.conn.commit()  # ✅ Commit before running VACUUM

        db.cursor.execute("VACUUM;")  # ✅ Now it's safe to execute
        print("Tables cleared and database optimized successfully")
    except Exception as e:
        print(f"Error clearing tables: {e}")
else:
    print("One or both tables do not exist")

# Close database connection
db.conn.close()
print("Database connection closed")
