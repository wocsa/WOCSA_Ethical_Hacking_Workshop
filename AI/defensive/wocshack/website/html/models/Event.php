<?php

class Event
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Get all events for a specific association
    public function getEventsByAssociation($associationId)
    {
        $stmt = $this->pdo->prepare('
            SELECT e.id, e.name, e.description, e.event_date, e.picture
            FROM events e
            WHERE e.association_id = ? AND e.event_date > NOW()
            ORDER BY e.event_date DESC
        ');
        $stmt->execute([$associationId]);
        return $stmt->fetchAll();
    }

    // Create a new event (for admin users)
    public function createEvent($name, $description, $eventDate, $picture, $associationId)
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO events (name, description, event_date, picture, association_id)
            VALUES (?, ?, ?, ?, ?)
        ');
        return $stmt->execute([$name, $description, $eventDate, $picture, $associationId]);
    }

    // Get all past events for a specific association
    public function getPastEventsByAssociation($associationId)
    {
        $stmt = $this->pdo->prepare('
            SELECT e.id, e.name, e.description, e.event_date, e.picture
            FROM events e
            WHERE e.association_id = ? AND e.event_date < NOW()
            ORDER BY e.event_date DESC
        ');
        $stmt->execute([$associationId]);
        return $stmt->fetchAll();
    }

    // Get event feedback for a specific event
    public function getEventFeedbacks($eventId)
    {
        $stmt = $this->pdo->prepare('
            SELECT u.username, ef.feedback, u.profile_picture, u.uuid
            FROM event_feedbacks ef
            JOIN users u ON ef.user_id = u.id
            WHERE ef.event_id = ?
        ');
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    // Add feedback for a specific event
    public function addFeedback($eventId, $userId, $feedback)
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO event_feedbacks (event_id, user_id, feedback)
            VALUES (?, ?, ?)
        ');
        return $stmt->execute([$eventId, $userId, $feedback]);
    }

    // Check if a user is an admin of an association
    public function isUserAdmin($userId, $associationId)
    {
        $stmt = $this->pdo->prepare('
            SELECT 1 FROM user_associations
            WHERE user_id = ? AND association_id = ? AND is_admin = 1
        ');
        $stmt->execute([$userId, $associationId]);
        return $stmt->fetch() !== false;
    }

}
?>
