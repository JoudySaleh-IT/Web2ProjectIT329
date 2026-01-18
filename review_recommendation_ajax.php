<?php
session_start();
require "db_connect.php";
require "config.php";

// Check if user is educator
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recId = (int)($_POST['rec_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $approve = $_POST['approve'] ?? '';
    
    if (!$recId || !in_array($approve, ['yes', 'no'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 1. Get the recommended question details
        $getStmt = $pdo->prepare("
            SELECT rq.*, q.id as quiz_id 
            FROM RecommendedQuestion rq 
            JOIN Quiz q ON q.id = rq.quizID 
            WHERE rq.id = ? AND q.educatorID = ?
        ");
        $getStmt->execute([$recId, $_SESSION['user_id']]);
        $recommended = $getStmt->fetch();
        
        if (!$recommended) {
            throw new Exception("Recommendation not found");
        }
        
        $newStatus = ($approve === 'yes') ? 'approved' : 'disapproved';
        
        // 2. Update the recommendation status and comments
        $updateStmt = $pdo->prepare("
            UPDATE RecommendedQuestion 
            SET status = ?, comments = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$newStatus, $comment, $recId]);
        
        // 3. If approved, add to educator's quiz
        if ($approve === 'yes') {
            $insertStmt = $pdo->prepare("
                INSERT INTO QuizQuestion 
                (quizID, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $recommended['quizID'],
                $recommended['question'],
                $recommended['questionFigureFileName'],
                $recommended['answerA'],
                $recommended['answerB'],
                $recommended['answerC'],
                $recommended['answerD'],
                $recommended['correctAnswer']
            ]);
            
            // If there's a figure file, copy it to questions directory
            if (!empty($recommended['questionFigureFileName'])) {
                $source = UPLOAD_DIR_RECOMMENDED . $recommended['questionFigureFileName'];
                $dest = UPLOAD_DIR_QUESTIONS . $recommended['questionFigureFileName'];
                if (file_exists($source) && !file_exists($dest)) {
                    copy($source, $dest);
                }
            }
        }
        
        $pdo->commit();
        
        // Return success
        echo json_encode(['success' => true]);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}
?>