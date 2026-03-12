<?php

class Analytics
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Main entry point to get statistics based on user role
     *
     * @param string $userId
     * @param string $role 'normal', 'association_admin', 'global_admin'
     * @param string|null $associationIds Only required for association admins
     * @return array
     */
    public function getRoleBasedStats($userId, $role, $associationIds = null)
    {
        switch ($role) {
            case 'normal':
                return $this->getUserStatistics($userId);

            case 'association_admin':
                if (!$associationIds) {
                    return ['error' => 'Association ID is required for Association Admins.'];
                }
                // Check if it's a string and convert to array
                if (is_string($associationIds)) {
                    $associationIds = explode(',', $associationIds); // Convert string to array
                }
                foreach ($associationIds as $associationId) {
                    if (!$this->isAssociationAdmin($userId, $associationId)) {
                        return ['error' => 'Access denied.'];
                    }
                }
                return $this->getAssociationStatisticsMultiple($associationIds);

            case 'global_admin':
                return [
                    'total_users' => $this->getTotalUsers(),
                    'total_associations' => $this->getTotalAssociations(),
                    'total_feedbacks' => $this->getTotalFeedbacks(),
                    'feedbacks_by_association' => $this->getFeedbacksCountByAssociation(),
                    'users_by_association' => $this->getUsersCountByAssociation(),
                ];

            default:
                return ['error' => 'Invalid role.'];
        }
    }

    // Check if a user is an admin of a specific association
    private function isAssociationAdmin($userId, $associationIds)
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM user_associations
            WHERE user_id = :userId AND association_id = :associationIds AND is_admin = 1
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':associationIds', $associationIds, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // Get user-specific statistics
    public function getUserStatistics($userId)
    {
        $stmt = $this->pdo->prepare('
            SELECT u.username,
                   COUNT(ua.association_id) AS associations_count,
                   COUNT(t.id) AS tickets_count,
                   COUNT(f.id) AS feedback_count
            FROM users u
            LEFT JOIN user_associations ua ON u.id = ua.user_id
            LEFT JOIN tickets t ON u.id = t.user_id
            LEFT JOIN event_feedbacks f ON u.id = f.user_id
            WHERE u.id = :userId
            GROUP BY u.id
        ');
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get statistics for a specific association
    public function getAssociationStatistics($associationId)
    {
        $stmt = $this->pdo->prepare('
            SELECT a.name,
                   COUNT(ua.user_id) AS users_count,
                   COUNT(f.id) AS feedback_count
            FROM associations a
            LEFT JOIN user_associations ua ON a.id = ua.association_id
            LEFT JOIN event_feedbacks f ON a.id = f.event_id
            WHERE a.id = :associationId
            GROUP BY a.id
        ');
        $stmt->bindParam(':associationId', $associationId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get statistics for a bunch of specifics associations
    public function getAssociationStatisticsMultiple($associationIds)
    {
        $statistics = [];
        foreach ($associationIds as $associationId) {
            // Call the existing getAssociationStatistics method for each association
            $statistics[] = $this->getAssociationStatistics($associationId);
        }
        return $statistics;
    }

    // Get total users count
    public function getTotalUsers()
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total_users FROM users');
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get total associations count
    public function getTotalAssociations()
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total_associations FROM associations');
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get total feedbacks count
    public function getTotalFeedbacks()
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total_feedbacks FROM event_feedbacks');
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get feedbacks count grouped by association
    public function getFeedbacksCountByAssociation()
    {
        $stmt = $this->pdo->prepare('
            SELECT a.name, COUNT(f.id) AS feedback_count
            FROM associations a
            LEFT JOIN events e ON a.id = e.association_id
            LEFT JOIN event_feedbacks f ON e.id = f.event_id
            GROUP BY a.id, a.name
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get users count grouped by association
    public function getUsersCountByAssociation()
    {
        $stmt = $this->pdo->prepare('
            SELECT a.name, COUNT(ua.user_id) AS user_count
            FROM associations a
            LEFT JOIN user_associations ua ON a.id = ua.association_id
            GROUP BY a.id
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exportToXml($data, $rootElementName = 'data', $childElementName = 'item')
    {
        // Create a new DOMDocument instance
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Create the root element
        $root = $dom->createElement($rootElementName);
        $dom->appendChild($root);

        // Function to recursively add data
        $addDataToNode = function ($data, $node) use ($dom, $childElementName, &$addDataToNode) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $elementName = is_numeric($key) ? $childElementName : $key;
                    $childNode = $dom->createElement($elementName);

                    if (is_array($value)) {
                        $addDataToNode($value, $childNode);
                    } else {
                        $childNode->appendChild($dom->createTextNode($value));
                    }

                    $node->appendChild($childNode);
                }
            }
        };

        // Add data to the root node
        $addDataToNode($data, $root);

        // Return the XML string
        return $dom->saveXML();
    }
}
