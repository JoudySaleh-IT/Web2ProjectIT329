<?php
session_start();
require "db_connect.php";
require "config.php";

// Security check - ensure user is logged in as educator
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    echo json_encode(['success' => false, 'message' => 'Please log in as an educator']);
    exit;
}

// Get question ID and quiz ID from POST request (AJAX)
$questionId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;

if ($questionId === 0 || $quizId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Verify the question belongs to the current educator
    $verifyStmt = $pdo->prepare("
        SELECT qq.questionFigureFileName 
        FROM QuizQuestion qq 
        JOIN Quiz q ON qq.quizID = q.id 
        WHERE qq.id = ? AND q.educatorID = ?
    ");
    $verifyStmt->execute([$questionId, $_SESSION['user_id']]);
    $question = $verifyStmt->fetch();

    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'Question not found or no permission']);
        exit;
    }

    // Get the image filename before deleting the question
    $figureFileName = $question['questionFigureFileName'];

    // Delete the question from database
    $deleteStmt = $pdo->prepare("DELETE FROM QuizQuestion WHERE id = ?");
    $deleteSuccess = $deleteStmt->execute([$questionId]);

    if ($deleteSuccess) {
        // Delete the image file if it exists
        if ($figureFileName && file_exists(UPLOAD_DIR_QUESTIONS . $figureFileName)) {
            unlink(UPLOAD_DIR_QUESTIONS . $figureFileName);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete question']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>