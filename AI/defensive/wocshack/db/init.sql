-- Create users table with bio, profile picture, and admin status
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL, -- UUID for user identification
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT DEFAULT '',
    profile_picture VARCHAR(255) DEFAULT "../uploads/default.jpg", -- Path to profile picture
    is_admin TINYINT(1) DEFAULT 0, -- Boolean flag to indicate if the user is an admin (1 for true, 0 for false)
    reset_token VARCHAR(255) DEFAULT NULL, -- Token for password reset
    reset_token_expiration TIMESTAMP DEFAULT NULL, -- Token expiration time
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create associations table with address and contact email
CREATE TABLE associations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL, -- UUID for user identification
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    address VARCHAR(255) NOT NULL, -- Address of the association
    contact_email VARCHAR(100) NOT NULL, -- Contact email of the association
    created_by INT, -- Reference to the user who created the association
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create user_associations table for many-to-many relationship
CREATE TABLE user_associations (
    user_id INT,
    association_id INT,
    is_admin BOOLEAN DEFAULT FALSE, -- Flag to indicate if user is an admin of the association
    PRIMARY KEY (user_id, association_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE CASCADE
);

-- Create forums tables for association discussions
CREATE TABLE forums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    association_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE CASCADE
);

-- Create topics table for forum discussions
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,                  -- Le contenu du commentaire
    created_by INT NOT NULL,                -- L'utilisateur qui a écrit le commentaire
    forum_id INT NOT NULL,                  -- L'ID du forum auquel ce commentaire appartient
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (forum_id) REFERENCES forums(id) ON DELETE CASCADE  -- Suppression en cascade des commentaires si le forum est supprimé
);

-- Create faqs table for user-submitted questions to admins
CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- ID of the user who asked the question
    question TEXT NOT NULL, -- The question submitted by the user
    answer TEXT DEFAULT NULL, -- Admin's answer to the question
    is_answered TINYINT(1) DEFAULT 0, -- Whether the question has been answered (0 for no, 1 for yes)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When the question was created
    answered_at TIMESTAMP DEFAULT NULL, -- When the question was answered
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create documents table to manage documents related to associations
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    association_id INT NOT NULL, -- The association to which the document belongs
    title VARCHAR(255) NOT NULL, -- Title of the document
    file_path VARCHAR(255) NOT NULL, -- Path to the document file
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (association_id) REFERENCES associations(id) ON DELETE CASCADE
);

-- Create tickets table
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,  -- User who created the ticket
    title VARCHAR(255) NOT NULL,  -- Title of the ticket
    description TEXT NOT NULL,  -- Description of the issue
    status ENUM('open', 'in_progress', 'resolved') DEFAULT 'open',  -- Status of the ticket
    admin_id INT DEFAULT NULL,  -- Admin who handles the ticket (can be null until assigned)
    response TEXT DEFAULT NULL,  -- Admin response to the ticket
    url VARCHAR(255) DEFAULT NULL,  -- Optional URL for the ticket issue
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Ticket creation time
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Last updated time
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create ticket_comments table to allow users and admins to discuss the ticket
CREATE TABLE ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT,
    user_id INT,
    ticket_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Events Table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    association_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Adding created_at for event creation timestamp
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Track event updates
    FOREIGN KEY (association_id) REFERENCES associations(id)
);

-- Event Feedback Table
CREATE TABLE event_feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    feedback TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Track feedback updates
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create transactions table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    association_name VARCHAR(100) NOT NULL,
    donator_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT DEFAULT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create bank_accounts table
CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    association_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    routing_number VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);