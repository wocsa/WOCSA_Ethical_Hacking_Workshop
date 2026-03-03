<?php

class Forum
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Create forum
    public function createForum($associationId, $title, $description)
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO forums (association_id, title, description) 
            VALUES (?, ?, ?)
        ');
        return $stmt->execute([$associationId, $title, $description]);
    }

    // Get forum association
    public function getForumsByAssociationId($associationId)
    {
        $stmt = $this->pdo->prepare('
            SELECT id, title, description, created_at FROM forums 
            WHERE association_id = ?
        ');
        $stmt->execute([$associationId]);
        return $stmt->fetchAll();
    }

    // Get forum by his ID
    public function getForumById($forumId)
    {
        $stmt = $this->pdo->prepare('
            SELECT id, title, description, created_at FROM forums 
            WHERE id = ?
        ');
        $stmt->execute([$forumId]);
        return $stmt->fetch();
    }

    // Retrieve forum comments along with user information
    public function getCommentsByForumId($forumId)
    {
        $stmt = $this->pdo->prepare('
        SELECT
            c.id AS comment_id,
            c.content,
            c.created_at AS comment_created_at,
            u.id AS user_id,
            u.username,
            u.profile_picture,
            u.uuid
        FROM
            comments c
        JOIN
            users u ON c.created_by = u.id
        WHERE
            c.forum_id = ?
        ORDER BY
            c.created_at
    ');
        $stmt->execute([$forumId]);
        return $stmt->fetchAll();
    }

    // Create a new comment in a forum
    public function createComment($content, $createdBy, $forumId)
    {
        $stmt = $this->pdo->prepare('INSERT INTO comments (content, created_by, forum_id) VALUES (?, ?, ?)');
        $stmt->execute([$content, $createdBy, $forumId]);
        return $this->pdo->lastInsertId();
    }

    // Delete a forum
    public function deleteForum($forumId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM forums WHERE id = ?');
        return $stmt->execute([$forumId]);
    }
}
