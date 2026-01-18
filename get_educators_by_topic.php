<?php
// get_educators_by_topic.php
session_start();
require "db_connect.php";
require "config.php";

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Please log in']);
    exit;
}

// Check if topic_id is provided
if (!isset($_GET['topic_id']) || empty($_GET['topic_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Topic ID is required']);
    exit;
}

$topicId = (int)$_GET['topic_id'];

try {
    // Query to get educators who have quizzes in the selected topic
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.firstName, u.lastName 
        FROM User u
        INNER JOIN Quiz q ON u.id = q.educatorID
        WHERE u.userType = 'educator' 
        AND q.topicID = ?
        ORDER BY u.firstName, u.lastName ASC
    ");
    
    $stmt->execute([$topicId]);
    $educators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($educators);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>