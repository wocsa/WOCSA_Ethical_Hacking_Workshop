import sqlite3
import hashlib


class Database:
    def __init__(self, file_path):
        # Connect to the SQLite database (it will create the file if it doesn't exist)
        self.conn = sqlite3.connect(file_path, check_same_thread=False)  # Use file_path instead of a hardcoded filename
        self.cursor = self.conn.cursor()

        # Create the users table if it doesn't exist
        self.cursor.execute('''CREATE TABLE IF NOT EXISTS users (
                                Name TEXT NOT NULL PRIMARY KEY, 
                                Password TEXT NOT NULL)''')
        self.cursor.execute('''CREATE TABLE IF NOT EXISTS Emails (
                                Id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                Source TEXT NOT NULL,
                                Destination TEXT NOT NULL,
                                Subject TEXT NOT NULL,
                                Content TEXT NOT NULL)''')

        self.conn.commit()

    def insert_user(self, name, password):
        """Insert a new user into the database"""
        self.cursor.execute('''INSERT INTO users (Name, Password) VALUES (?, ?)''', (name, password))
        self.conn.commit()

    def get_user_by_id(self, user_id):
        """Fetch a user by their ID"""
        self.cursor.execute('''SELECT * FROM users WHERE Name = ?''', (user_id,))
        return self.cursor.fetchone()  # Returns the first matching record or None if not found

    def get_user_password(self, user_id):
        """Fetch a user's password by their ID"""
        self.cursor.execute('''SELECT Password FROM users WHERE Name = ?''', (user_id,))
        return self.cursor.fetchone()  # Returns the first matching record or None if not found

    def get_all_users(self):
        """Fetch all users"""
        self.cursor.execute('''SELECT * FROM users''')
        return self.cursor.fetchall()  # Returns a list of all records

    def update_user_password(self, user_id, new_password):
        """Update the password for a specific user by ID"""
        self.cursor.execute('''UPDATE users SET Password = ? WHERE Name = ?''', (new_password, user_id))
        self.conn.commit()

    def delete_user(self, user_id):
        """Delete a user by their ID"""
        self.cursor.execute('''DELETE FROM users WHERE Name = ?''', (user_id,))
        self.conn.commit()

    def delete_email_by_id(self, Id):
        """Delete a user by their ID"""
        self.cursor.execute('''DELETE FROM Emails WHERE Destination = ?''', (Id,))
        self.conn.commit()

    def get_email_by_id(self, id):
        """Fetch an email from an email id"""
        self.cursor.execute('''SELECT * FROM Emails WHERE Id = ?''', (id,))
        return self.cursor.fetchall()  # Returns a list of all records

    def get_user_emails(self, user):
        """Fetch all emails from an email user address"""
        self.cursor.execute('''SELECT * FROM Emails WHERE Destination = ?''', (user,))
        return self.cursor.fetchall()  # Returns a list of all records

    def add_email(self, Source, Destination, Subject, Content):
        self.cursor.execute('''INSERT INTO Emails (Source, Destination, Subject, Content) VALUES (?, ?, ?, ?)''',
                            (Source, Destination, Subject, Content))
        self.conn.commit()

    def close(self):
        """Close the connection to the database"""
        self.conn.close()


class Handler:
    def __init__(self):
        '''Class Handler made to handle all the interactions between Flask frontend, Backend and database'''
        self.database = Database('DataBase/DataBase.db')

    def verify_user_availability(self, username, password, confirm_password):
        # verify if the username and password submited is valid.
        if self.database.get_user_by_id(username) is not None:
            return -1

        elif len(password) < 8:
            return -2

        elif password != confirm_password:
            return -3
        return 0

    def check_connect(self, username, password):
        if self.database.get_user_by_id(username) is None:
            return -1
        if self.database.get_user_password(username)[0] != hashlib.sha256(password.encode()).hexdigest():
            return -2
        return 0

    def get_emails(self, email):
        return self.database.get_user_emails(email)

    def get_email_by_id(self, id):
        return self.database.get_email_by_id(id)

    def send_email(self, Source, Destination, Subject, Content):
        if len(Source) == 0 or len(Destination) == 0 or len(Subject) == 0 or len(Content) == 0:
            return -1
        if self.database.get_user_by_id(Destination) is not None:
            self.database.add_email(Source, Destination, Subject, Content)
            return 0
        return -2

    def add_user(self, username, password):
        # A function to insert the user in the database
        self.database.insert_user(username, password)

    def delete_user(self, username, password):
        req = self.check_connect(username, password)
        if req == 0:
            self.database.delete_user(username)
            self.database.delete_email_by_id(username)
            return 0
        return req

    def update_user_password(self, usermane, password, new):
         req = self.check_connect(usermane, password)
         if req == 0:
            password = hashlib.sha256(new.encode())
            self.database.update_user_password(usermane, password.hexdigest())
         return req

    def get_cookie(self, username):
        password = self.database.get_user_password(username)
        data = f"{username} + {password}".encode()
        cookie = hashlib.sha256(data)
        return cookie.hexdigest()
