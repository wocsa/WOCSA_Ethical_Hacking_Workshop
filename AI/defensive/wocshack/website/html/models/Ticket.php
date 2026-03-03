<?php

class Ticket
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new ticket with optional URL
    public function createTicket($userId, $title, $description, $url = null)
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO tickets (user_id, title, description, url) VALUES (?, ?, ?, ?)
        ');
        return $stmt->execute([$userId, $title, $description, $url]);
    }

    // Get all tickets for a user
    public function getUserTickets($userId)
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM tickets WHERE user_id = ?
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Get all open tickets for admin
    public function getOpenTickets()
    {
        $stmt = $this->pdo->prepare('
            SELECT t.*, u.username AS user_name FROM tickets t
            JOIN users u ON t.user_id = u.id
            WHERE t.status = "open"
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get all tickets for admin (with resolved and in-progress tickets)
    public function getAllTickets()
    {
        $stmt = $this->pdo->prepare('
            SELECT t.*, u.username AS user_name, a.username AS admin_name FROM tickets t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN users a ON t.admin_id = a.id
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Update ticket status, response, and URL by admin
    public function updateTicket($ticketId, $status, $response, $adminId, $url = null)
    {
        if ($url === null) {
            $stmt = $this->pdo->prepare('
                UPDATE tickets SET status = ?, response = ?, admin_id = ? WHERE id = ?
            ');
            return $stmt->execute([$status, $response, $adminId, $ticketId]);
        } else {
            $stmt = $this->pdo->prepare('
                UPDATE tickets SET status = ?, response = ?, admin_id = ?, url = ? WHERE id = ?
            ');
            return $stmt->execute([$status, $response, $adminId, $url, $ticketId]);
        }
    }
}
