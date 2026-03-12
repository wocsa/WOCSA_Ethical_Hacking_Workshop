<?php

class TicketComment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Add a comment to a ticket
    public function addTicketComment($ticketId, $userId, $ticketComment) {
        $stmt = $this->pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, ticket_comment) VALUES (?, ?, ?)");
        return $stmt->execute([$ticketId, $userId, $ticketComment]);
    }

    // Get all comments for a specific ticket
    public function getTicketComments($ticketId) {
        $stmt = $this->pdo->prepare("SELECT tc.ticket_comment, u.username, u.uuid, u.profile_picture FROM ticket_comments tc JOIN users u ON tc.user_id = u.id WHERE tc.ticket_id = ? ORDER BY tc.created_at ASC");
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
