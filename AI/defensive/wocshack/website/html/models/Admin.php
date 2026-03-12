<?php

class Admin
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Get all admins
    public function getAllAdmins()
    {
        $stmt = $this->pdo->query('SELECT id, username, email FROM users WHERE is_admin = 1');
        return $stmt->fetchAll();
    }

    // Promote an existing user to admin
    public function promoteUserToAdmin($userId)
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
        return $stmt->execute([$userId]);
    }

    // Delete an admin by ID
    public function deleteAdmin($adminId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ? AND is_admin = 1');
        return $stmt->execute([$adminId]);
    }

    // Get all users (to select from existing users)
    public function getAllUsers()
    {
        $stmt = $this->pdo->query('SELECT id, username, email FROM users WHERE is_admin = 0');
        return $stmt->fetchAll();
    }

    public function deleteUser($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }
    

    
}
