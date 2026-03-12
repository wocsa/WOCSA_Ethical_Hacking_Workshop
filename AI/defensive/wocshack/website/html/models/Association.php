<?php

class Association
{
    private $pdo;

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

    // Constructor
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Create a new association in the database
    public function createAssociation($name, $description, $address, $contactEmail, $userId)
    {
        // Generate a UUID
        $uuid = $this->generateUUID();

        // Prepare to insert the new association
        $stmt = $this->pdo->prepare('
            INSERT INTO associations (name, description, address, contact_email, created_by, uuid) VALUES (?, ?, ?, ?, ?, ?)
        ');

        // Execute the insertion
        $stmt->execute([$name, $description, $address, $contactEmail, $userId, $uuid]);

        // Get the ID of the newly created association
        $associationId = $this->pdo->lastInsertId();

        // Now associate the creator with the association as admin
        $stmt = $this->pdo->prepare('
            INSERT INTO user_associations (user_id, association_id, is_admin) VALUES (?, ?, ?)
        ');

        // Insert the user as an admin
        $stmt->execute([$userId, $associationId, true]);

        // Return the ID of the created association
        return $associationId;
    }

    // Get association by ID from the database
    public function getAssociationById($associationId)
    {
        // Get association data
        $stmt = $this->pdo->prepare('SELECT id, uuid, name, description, address, contact_email, created_by FROM associations WHERE id = ?');
        $stmt->execute([$associationId]);
        $association = $stmt->fetch();

        // Get members
        $stmt = $this->pdo->prepare('
            SELECT user_id FROM user_associations WHERE association_id = ?
        ');
        $stmt->execute([$associationId]);
        $members = $stmt->fetchAll();

        // Add members to association
        $association['members'] = $members;

        return $association;
    }

    // Get association by UUID from the database
    public function getAssociationByUUID($uuid)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM associations WHERE uuid = ?');
        $stmt->execute([$uuid]);
        return $stmt->fetch();
    }

    // Get all associations of a user, separated by admin and member roles
    public function getAssociationsByRole($accountId)
    {
        // Prepare the query to retrieve associations and their status
        $stmt = $this->pdo->prepare('
            SELECT association_id, is_admin FROM user_associations WHERE user_id = ?
        ');
        $stmt->execute([$accountId]);
        $associationRoles = $stmt->fetchAll();

        // Separate associations based on role
        $associations = ['admin' => [], 'member' => []];
        foreach ($associationRoles as $associationRole) {
            $association = $this->getAssociationById($associationRole['association_id']);
            if ($associationRole['is_admin']) {
                $associations['admin'][] = $association;
            } else {
                $associations['member'][] = $association;
            }
        }

        return $associations;
    }

    // Get all associations a user is a part of
    public function getUserAssociations($userId)
    {
        $stmt = $this->pdo->prepare('
            SELECT a.id, a.uuid, a.name FROM associations a
            JOIN user_associations ua ON a.id = ua.association_id
            WHERE ua.user_id = ?
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Get all associations a user is a part of
    public function getUserAssociationsWhereAdmin($userId)
    {
        $stmt = $this->pdo->prepare('
            SELECT a.id, a.uuid, a.name FROM associations a
            JOIN user_associations ua ON a.id = ua.association_id
            WHERE ua.is_admin = true and ua.user_id = ?
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Get all associations from the database
    public function getAllAssociations()
    {
        $stmt = $this->pdo->prepare('SELECT id, name, uuid FROM associations');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get association by name (used for research)
    public function getAssociationByName($name)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM associations WHERE name = ?');
        $stmt->execute([$name]);
        return $stmt->fetch();
    }

    // Search for associations by name (used for research)
    public function searchAssociations($query)
    {
        $sql = "SELECT * FROM associations WHERE name LIKE '%$query%'";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update an association in the database
    public function updateAssociation($associationId, $name, $description, $address, $contactEmail)
    {
        $stmt = $this->pdo->prepare('UPDATE associations SET name = ?, description = ?, address = ?, contact_email = ? WHERE uuid = ?');
        return $stmt->execute([$name, $description, $address, $contactEmail, $associationId]);
    }

    // Delete an association from the database
    public function deleteAssociation($associationUUID)
    {
        $stmt = $this->pdo->prepare('DELETE FROM associations WHERE uuid = ?');
        return $stmt->execute([$associationUUID]);
    }

    // Update association information
    public function updateAssociationInfo($associationUUID, $name, $description, $address, $contactEmail)
    {
        $stmt = $this->pdo->prepare('UPDATE associations SET name = ?, description = ?, address = ?, contact_email = ? WHERE uuid = ?');
        return $stmt->execute([$name, $description, $address, $contactEmail, $associationUUID]);
    }

    // Add members to an association
    public function addMembers($associationId, $userIds)
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO user_associations (user_id, association_id) VALUES (?, ?)
        ');

        foreach ($userIds as $userId) {
            $stmt->execute([$userId, $associationId]);
        }
    }

    // Delete member from an association
    public function deleteMember($associationId, $userId)
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM user_associations WHERE user_id = ? AND association_id = ?
        ');
        return $stmt->execute([$userId, $associationId]);
    }

    // Get members by association ID (including username and UUID)
    public function getMembersByAssociationId($associationId)
    {
        $stmt = $this->pdo->prepare('
            SELECT u.id AS user_id, u.username, u.uuid, u.email, ua.is_admin, u.profile_picture
            FROM user_associations ua
            JOIN users u ON ua.user_id = u.id
            WHERE ua.association_id = ?
        ');
        $stmt->execute([$associationId]);
        return $stmt->fetchAll();
    }

    // Function to export members to CSV
    public function exportMembersToCSV($associationId)
    {
        $members = $this->getMembersByAssociationId($associationId);

        // Create a temporary file in memory
        $csvFile = fopen('php://output', 'w');
        if ($csvFile === false) {
            throw new Exception("Unable to create the CSV file.");
        }

        // Add CSV header
        fputcsv($csvFile, ['UserID', 'Username', 'Email']);

        // Add member data
        foreach ($members as $member) {
            fputcsv($csvFile, [$member['user_id'], $member['username'], $member['email']]);
        }

        // Close the file
        fclose($csvFile);
    }

    // Function to import members from a CSV file
    public function importMembersFromCSV($associationId, $filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("The file does not exist.");
        }
    
        // Lists to track results
        $userIdsToAdd = [];
        $notAddedUsers = [];
        $notFoundEmails = [];
        $currentMembers = $this->getMembersByAssociationId($associationId);
    
        // Get IDs of current admins
        $adminIds = array_filter($currentMembers, fn($member) => $member['is_admin']);
        $adminIds = array_column($adminIds, 'user_id');
        $currentMemberIds = array_column($currentMembers, 'user_id');
    
        // Debug: Log file content
        $fileContent = file_get_contents($filePath);
        error_log("CSV File Content:\n" . $fileContent);
    
        // Read the CSV file
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Skip header row
            $header = fgetcsv($handle, 1000, ',');
            error_log("Header Row: " . print_r($header, true));
    
            $rowNumber = 1; // Track row number for debugging
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNumber++;
                error_log("Row $rowNumber: " . print_r($data, true));
    
                // Ensure the row has enough columns
                if (count($data) < 3) {
                    error_log("Row $rowNumber skipped: Insufficient columns (" . count($data) . ")");
                    continue;
                }
    
                $email = trim($data[2]); // Email is in the third column
                error_log("Processing email: $email");
    
                // Skip empty email
                if (empty($email)) {
                    error_log("Row $rowNumber skipped: Empty email");
                    continue;
                }
    
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    error_log("Row $rowNumber skipped: Invalid email format ($email)");
                    $notFoundEmails[] = $email;
                    continue;
                }
    
                // Check if the user exists
                $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
    
                if ($user) {
                    error_log("User found: ID={$user['id']}, Email=$email");
                    // Check if user is already a member
                    if (in_array($user['id'], $currentMemberIds)) {
                        $notAddedUsers[] = [
                            'email' => $email,
                            'reason' => 'The user is already a member of the association.'
                        ];
                        error_log("Row $rowNumber skipped: User ID {$user['id']} is already a member");
                    } elseif (in_array($user['id'], $adminIds)) {
                        $notAddedUsers[] = [
                            'email' => $email,
                            'reason' => 'The user is an administrator and cannot be added.'
                        ];
                        error_log("Row $rowNumber skipped: User ID {$user['id']} is an admin");
                    } else {
                        // Add user to the list if not already in the batch
                        if (!in_array($user['id'], $userIdsToAdd)) {
                            $userIdsToAdd[] = $user['id'];
                            error_log("User ID {$user['id']} added to batch");
                        }
                    }
                } else {
                    $notFoundEmails[] = $email;
                    error_log("Row $rowNumber: Email not found in database ($email)");
                }
            }
            fclose($handle);
        } else {
            throw new Exception("Failed to open CSV file.");
        }
    
        // Remove users not in the CSV (except admins)
        foreach ($currentMembers as $member) {
            if (!in_array($member['user_id'], $userIdsToAdd) && !in_array($member['user_id'], $adminIds)) {
                $this->deleteMember($associationId, $member['user_id']);
                error_log("Removed member: User ID {$member['user_id']}");
            }
        }
    
        // Add new members
        if (!empty($userIdsToAdd)) {
            $this->addMembers($associationId, $userIdsToAdd);
            error_log("Added users: " . implode(', ', $userIdsToAdd));
        }
    
        // Debug: Log results
        error_log("Import Results: Added=" . count($userIdsToAdd) . ", Not Added=" . print_r($notAddedUsers, true) . ", Not Found=" . print_r($notFoundEmails, true));
    
        return [
            'added_count' => count($userIdsToAdd),
            'not_added_users' => $notAddedUsers,
            'not_found_emails' => $notFoundEmails,
        ];
    }

       

  
    // Function to handle file upload and import members
    public function handleFileUploadAndImport($associationId, $uploadedFile)
    {
        $uploadDir = './uploads/'; // Ensure this directory exists and is writable
        $uploadedFilePath = $uploadDir . basename($uploadedFile['name']);
        $errorMessage = null;

        // Step 1: Check file upload error
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = "File upload failed with error code: " . $uploadedFile['error'];
        }

        // Step 2: Check file extension
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'csv') {
            $errorMessage = "Only CSV files are allowed.";
        }

        // Check the MIME type of the file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);
        if ($mimeType !== 'text/plain' && $mimeType !== 'text/csv') {
            $errorMessage = "Invalid file type. Only CSV files are allowed.";
        }

        // Step 3: If checks pass, move the file and process it
        if (!isset($errorMessage)) {
            if (move_uploaded_file($uploadedFile['tmp_name'], $uploadedFilePath)) {
                try {
                    // Call the import method
                    $result = $this->importMembersFromCSV($associationId, $uploadedFilePath);

                    return [
                        'success' => true,
                        'message' => "File imported successfully.",
                        'added_count' => $result['added_count'],
                        'not_added_users' => $result['not_added_users'],
                        'not_found_emails' => $result['not_found_emails'],
                    ];
                } catch (Exception $e) {
                    return [
                        'success' => false,
                        'message' => "Error during import: " . $e->getMessage(),
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => "Failed to move the uploaded file.",
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        }
    }
}
