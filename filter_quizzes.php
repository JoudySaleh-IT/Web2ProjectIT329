<?php
// filter_quizzes.php
session_start();
require "db_connect.php";
require "config.php";

// Allow access only via AJAX and for logged-in learners
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'learner') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get the selected topic ID from POST
$topicId = isset($_POST['topic_id']) && $_POST['topic_id'] !== '' ? (int)$_POST['topic_id'] : null;

// Build the query based on topic filter
$where = "";
$params = [];

if ($topicId) {
    $where = "WHERE q.topicID = ?";
    $params[] = $topicId;
}

// Query to get quizzes
$qSql = "
  SELECT
    q.id AS quiz_id,
    t.topicName,
    e.firstName AS educator_first,
    e.lastName  AS educator_last,
    e.photoFileName AS educator_photo,
    (SELECT COUNT(*) FROM QuizQuestion qq WHERE qq.quizID = q.id) AS question_count
  FROM Quiz q
  JOIN Topic t   ON t.id = q.topicID
  JOIN User  e   ON e.id = q.educatorID
  $where
  ORDER BY t.topicName ASC, q.id ASC
";

try {
    $qStmt = $pdo->prepare($qSql);
    $qStmt->execute($params);
    $quizzes = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Process educator photos
    foreach ($quizzes as &$quiz) {
        $quiz['educator_photo'] = UPLOAD_URL_USERS . ($quiz['educator_photo'] ?: DEFAULT_USER_PHOTO);
    }
    unset($quiz); // break the reference

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'quizzes' => $quizzes
    ]);
    
} catch (PDOException $e) {
    // Log error and return error response
    error_log("Database error in filter_quizzes.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>