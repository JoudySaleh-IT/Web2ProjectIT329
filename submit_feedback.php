<?php
session_start();
require "db_connect.php";

/* Security check - must be logged in as learner */
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'learner') {
    header("Location: login.php?error=Please+log+in+as+a+learner");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['quizID'])) {
    header("Location: LearnerHomepage.php?error=Invalid+feedback+submission");
    exit;
}

$quizId = (int)$_POST['quizID'];
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : null;

// Validate rating if provided (1-5)
if ($rating && ($rating < 1 || $rating > 5)) {
    header("Location: quiz_score.php?error=Invalid+rating&quizID=" . $quizId);
    exit;
}

// Check if at least rating or comments is provided
if (!$rating && !$comments) {
    header("Location: LearnerHomepage.php?message=No+feedback+submitted");
    exit;
}

// Verify quiz exists
try {
    $quizCheck = $pdo->prepare("SELECT id FROM Quiz WHERE id = ?");
    $quizCheck->execute([$quizId]);
    if (!$quizCheck->fetch()) {
        header("Location: LearnerHomepage.php?error=Quiz+not+found");
        exit;
    }
} catch (PDOException $e) {
    header("Location: LearnerHomepage.php?error=Database+error");
    exit;
}

// Insert feedback into database
try {
    $stmt = $pdo->prepare("INSERT INTO QuizFeedback (quizID, rating, comments) VALUES (?, ?, ?)");
    $stmt->execute([$quizId, $rating ?: null, $comments ?: null]);
    
    // Redirect to learner homepage with success message
    header("Location: LearnerHomepage.php?message=Feedback+submitted+successfully");
    exit;
    
} catch (PDOException $e) {
    header("Location: LearnerHomepage.php?error=Failed+to+save+feedback");
    exit;
}
?>
