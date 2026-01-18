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
    header("Location: editQ.php");
    exit;
}

// Get form data
$questionId = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
$questionText = trim($_POST['question']);
$answerA = trim($_POST['ansA']);
$answerB = trim($_POST['ansB']);
$answerC = trim($_POST['ansC']);
$answerD = trim($_POST['ansD']);
$correctAnswer = $_POST['correct'];

// Validate inputs
if ($questionId === 0 || empty($questionText) || empty($answerA) || empty($answerB) || empty($answerC) || empty($answerD) || empty($correctAnswer)) {
    header("Location: editQ.php?id=" . $questionId . "&error=Missing+required+fields");
    exit;
}

// Get current question details and verify it belongs to the current educator
$questionStmt = $pdo->prepare("
    SELECT qq.*, q.id as quizID 
    FROM QuizQuestion qq 
    JOIN Quiz q ON qq.quizID = q.id 
    WHERE qq.id = ? AND q.educatorID = ?
");
$questionStmt->execute([$questionId, $_SESSION['user_id']]);
$currentQuestion = $questionStmt->fetch();

if (!$currentQuestion) {
    header("Location: editQ.php?id=" . $questionId . "&error=Question+not+found+or+permission+denied");
    exit;
}

$quizId = $currentQuestion['quizID'];
$currentImage = $currentQuestion['questionFigureFileName'];

// Handle image upload
$figureFileName = $currentImage; // Keep current image by default

// Check if user wants to remove the selected image (only if no new file is uploaded)
$removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

if (isset($_FILES['figure']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK) {
    // New file is being uploaded - ignore the remove flag
    $figureFileName = saveUploadedImage('figure', UPLOAD_DIR_QUESTIONS, 'question_' . $quizId . '_' . time());
    
    // Delete old image if it exists and we're uploading a new one
    if ($currentImage && file_exists(UPLOAD_DIR_QUESTIONS . '/' . $currentImage)) {
        unlink(UPLOAD_DIR_QUESTIONS . '/' . $currentImage);
    }
} elseif ($removeImage) {
    // User wants to remove existing image and no new file uploaded
    if ($currentImage && file_exists(UPLOAD_DIR_QUESTIONS . '/' . $currentImage)) {
        unlink(UPLOAD_DIR_QUESTIONS . '/' . $currentImage);
    }
    $figureFileName = null;
}
// If neither condition is met, $figureFileName remains as $currentImage

// Update question in database
$updateStmt = $pdo->prepare("
    UPDATE QuizQuestion 
    SET question = ?, questionFigureFileName = ?, answerA = ?, answerB = ?, answerC = ?, answerD = ?, correctAnswer = ?
    WHERE id = ?
");

$success = $updateStmt->execute([
    $questionText,
    $figureFileName,
    $answerA,
    $answerB,
    $answerC,
    $answerD,
    $correctAnswer,
    $questionId
]);

if ($success) {
    // Redirect back to quiz page
    header("Location: quiz.php?id=" . $quizId);
    exit;
} else {
    header("Location: editQ.php?id=" . $questionId . "&error=Failed+to+update+question");
    exit;
}
?>