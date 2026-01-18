<?php
session_start();
require "db_connect.php";
require "config.php";
require "lib/upload_image.php";

// Security check - ensure user is logged in as educator
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    header("Location: login.php?error=Please+log+in+as+an+educator");
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: addQ.php");
    exit;
}

// Get form data
$quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
$question = trim($_POST['question']);
$answerA = trim($_POST['ansA']);
$answerB = trim($_POST['ansB']);
$answerC = trim($_POST['ansC']);
$answerD = trim($_POST['ansD']);
$correctAnswer = $_POST['correct'];

// Validate inputs
if ($quizId === 0 || empty($question) || empty($answerA) || empty($answerB) || empty($answerC) || empty($answerD) || empty($correctAnswer)) {
    header("Location: addQ.php?quiz_id=" . $quizId . "&error=Missing+required+fields");
    exit;
}

// Verify quiz belongs to current educator
$quizStmt = $pdo->prepare("SELECT id FROM Quiz WHERE id = ? AND educatorID = ?");
$quizStmt->execute([$quizId, $_SESSION['user_id']]);
if (!$quizStmt->fetch()) {
    header("Location: addQ.php?quiz_id=" . $quizId . "&error=Invalid+quiz+or+permission+denied");
    exit;
}

// Handle image upload
$figureFileName = null;

// Check if user wants to remove the selected image
if (!isset($_POST['remove_image']) || $_POST['remove_image'] !== '1') {
    if (isset($_FILES['figure']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK) {
        $figureFileName = saveUploadedImage('figure', UPLOAD_DIR_QUESTIONS, 'question_' . $quizId);
    }
}

// Insert question into database
$insertStmt = $pdo->prepare("
    INSERT INTO QuizQuestion (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$success = $insertStmt->execute([
    $quizId,
    $question,
    $figureFileName,
    $answerA,
    $answerB,
    $answerC,
    $answerD,
    $correctAnswer
]);

if ($success) {
    // Redirect back to quiz page
    header("Location: quiz.php?id=" . $quizId);
    exit;
} else {
    header("Location: addQ.php?quiz_id=" . $quizId . "&error=Failed+to+save+question");
    exit;
}
?>