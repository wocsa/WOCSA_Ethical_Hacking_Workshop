<?php

class Faq
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new FAQ question from a non-admin user
    public function createQuestion($userId, $question)
    {
        // Check if the user is non-admin before allowing them to ask a question
        $stmt = $this->pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && !$user['is_admin']) {
            $stmt = $this->pdo->prepare('INSERT INTO faqs (user_id, question, created_at) VALUES (?, ?, NOW())');
            return $stmt->execute([$userId, $question]);
        }

        return false; // Admins should not ask questions or invalid user ID
    }

    // Retrieve unanswered questions for admins to answer
    public function getUnansweredQuestions()
    {
        $stmt = $this->pdo->query('
            SELECT f.id, u.username, u.uuid, u.profile_picture, f.question, f.created_at
            FROM faqs f
            JOIN users u ON f.user_id = u.id
            WHERE f.is_answered = 0
            ORDER BY f.created_at ASC
        ');
        return $stmt->fetchAll();
    }

    // Admin answers a question
    public function answerQuestion($faqId, $answer, $adminId)
    {
        // Check if the user is an admin
        $stmt = $this->pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
        $stmt->execute([$adminId]);
        $user = $stmt->fetch();

        if ($user && $user['is_admin']) {
            $stmt = $this->pdo->prepare('
                UPDATE faqs 
                SET answer = ?, is_answered = 1, answered_at = NOW() 
                WHERE id = ?
            ');
            return $stmt->execute([$answer, $faqId]);
        }

        return false; // Only admins can answer questions or invalid admin ID
    }

    // Retrieve answered questions (for users to view)
    public function getAnsweredQuestions()
    {
        $stmt = $this->pdo->query('
            SELECT f.id, u.username, u.uuid, u.profile_picture, f.question, f.answer, f.answered_at
            FROM faqs f
            JOIN users u ON f.user_id = u.id
            WHERE f.is_answered = 1
            ORDER BY f.answered_at DESC
        ');
        return $stmt->fetchAll();
    }

    // Get all questions from a specific user (for the user's profile)
    public function getQuestionsByUser($userId)
    {
        $stmt = $this->pdo->prepare('
            SELECT id, question, answer, is_answered, created_at, answered_at
            FROM faqs 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
