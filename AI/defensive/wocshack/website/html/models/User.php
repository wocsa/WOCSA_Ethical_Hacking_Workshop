<?php

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Check if the user is an admin in any association
    public function isAdminInAnyAssociation($userId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_associations WHERE user_id = ? AND is_admin = 1");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() > 0;
    }

    // Get user by ID with associations and admin status
    public function getUserById($userId)
    {
        // Get user data including admin status
        $stmt = $this->pdo->prepare('SELECT id, uuid, username, email, bio, profile_picture, is_admin FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Get user's associations
        $stmt = $this->pdo->prepare('
            SELECT a.id, a.name
            FROM associations a
            JOIN user_associations ua ON a.id = ua.association_id
            WHERE ua.user_id = ?
        ');
        $stmt->execute([$userId]);
        $associations = $stmt->fetchAll();

        // Add associations to user
        $user['associations'] = $associations;

        return $user;
    }

    // Get user by username (used for login)
    public function getUserByUsername($username)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // Get user by email (used for password reset)
    public function getUserByEmail($email)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    // Create a new user
    public function createUser($username, $email, $password, $is_admin = 0)  // Add is_admin parameter
    {
        // Generate a UUID
        $uuid = $this->generateUUID();

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new user into the database
        $stmt = $this->pdo->prepare('
        INSERT INTO users (username, email, password, uuid, is_admin) VALUES (?, ?, ?, ?, ?)
    ');
        return $stmt->execute([$username, $email, $hashed_password, $uuid, $is_admin]);
    }

    // Function to generate a UUID
    private function generateUUID()
    {
        // Generate a version 4 (random) UUID
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    // Generate a reset token and set expiration
    public function generatePasswordResetToken($email)
    {
        $user = $this->getUserByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(16));
            $expiration = (new DateTime())->modify('+1 hour')->format('Y-m-d H:i:s');

            $stmt = $this->pdo->prepare('
                UPDATE users SET reset_token = ?, reset_token_expiration = ? WHERE email = ?
            ');
            $stmt->execute([$token, $expiration, $email]);

            return $token;
        }
        return false;
    }

    // Validate reset token
    public function validateResetToken($token)
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM users WHERE reset_token = ? AND reset_token_expiration > NOW()
        ');
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    // Reset user password
    public function resetPassword($token, $new_password)
    {
        $user = $this->validateResetToken($token);
        if ($user) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare('
                UPDATE users SET password = ?, reset_token = NULL, reset_token_expiration = NULL WHERE reset_token = ?
            ');
            return $stmt->execute([$hashed_password, $token]);
        }
        return false;
    }

    public function getUserByUUID($uuid)
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, bio, profile_picture FROM users WHERE uuid = ?');
        $stmt->execute([$uuid]);
        return $stmt->fetch();
    }

    // Authenticate user (for login)
    public function authenticateUser($username, $password)
    {
        $user = $this->getUserByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    // Update user details, including bio, profile picture, and admin status
    public function updateUser($userId, $username, $email, $bio, $profile_picture = null, $password = null)
    {
        // Récupérer la valeur actuelle de is_admin
        $currentUser = $this->getUserById($userId);
        $is_admin = $currentUser['is_admin'];
    
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare('
                UPDATE users SET username = ?, email = ?, bio = ?, profile_picture = ?, password = ?, is_admin = ? WHERE id = ?
            ');
            return $stmt->execute([$username, $email, $bio, $profile_picture, $hashed_password, $is_admin, $userId]);
        } else {
            $stmt = $this->pdo->prepare('
                UPDATE users SET username = ?, email = ?, bio = ?, profile_picture = ?, is_admin = ? WHERE id = ?
            ');
            return $stmt->execute([$username, $email, $bio, $profile_picture, $is_admin, $userId]);
        }
    }


    // Update user's associations
    public function updateUserAssociations($userId, $associationIds)
    {
        // Remove existing associations
        $stmt = $this->pdo->prepare('DELETE FROM user_associations WHERE user_id = ?');
        $stmt->execute([$userId]);

        // Add new associations
        $stmt = $this->pdo->prepare('INSERT INTO user_associations (user_id, association_id) VALUES (?, ?)');
        foreach ($associationIds as $associationId) {
            $stmt->execute([$userId, $associationId]);
        }
    }

    // Check if a user is an admin
    public function isAdmin($userId)
    {
        $stmt = $this->pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    // Check if user is admin in a specific association
    public function isAdminInAssociation($userId, $associationId)
    {
        $stmt = $this->pdo->prepare('SELECT is_admin FROM user_associations WHERE (user_id = ? AND association_id = ?)');
        $stmt->execute([$userId, $associationId]);
        return $stmt->fetchColumn();
    }

    // Delete a user by ID
    public function deleteUser($userId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$userId]);
    }

    // Method to verify password policy
    public function verifyPasswordPolicy($password)
    {
        $min_length = 8;
        $has_uppercase = preg_match('/[A-Z]/', $password);
        $has_number = preg_match('/\d/', $password);
        $has_special_char = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password);

        if (strlen($password) < $min_length) {
            return "Password must be at least 8 characters long.";
        }
        if (!$has_uppercase) {
            return "Password must include at least one uppercase letter.";
        }
        if (!$has_number) {
            return "Password must include at least one number.";
        }
        if (!$has_special_char) {
            return "Password must include at least one special character.";
        }

        return true;
    }

    public function validateAndUploadProfilePicture($file, $targetDir)
    {
        // Validate file type and size
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($fileType, $validExtensions)) {
            return 'Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.';
        } elseif ($file['size'] > $maxFileSize) {
            return 'File size exceeds 2MB limit.';
        } else {

            // Check if the file is a valid image
          
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return 'The uploaded file is not a valid image.';
            }
            
            // Define the new profile picture path
            $newProfilePicture = $targetDir . basename($file['name']);

            // Move the file only if it passes validation
            if (move_uploaded_file($file["tmp_name"], $newProfilePicture)) {
                // Return the new profile picture path
                return $newProfilePicture;
            } else {
                return 'Failed to upload file. Please try again.';
            }
        }
    }
}
