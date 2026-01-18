<?php
session_start();
require "db_connect.php";
require "config.php";

// Check if user is logged in and is a learner
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'learner') {
    header("Location: login.php?error=Please+log+in+as+a+learner");
    exit;
}

$learnerId = (int)$_SESSION['user_id'];

// Process form submission (Requirement 14b)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required = ['topic_id', 'educator_id', 'question', 'answerA', 'answerB', 'answerC', 'answerD', 'correctAnswer'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "All fields are required.";
            break;
        }
    }
    
    // Validate correct answer is one of A,B,C,D
    if (!empty($_POST['correctAnswer']) && !in_array($_POST['correctAnswer'], ['A', 'B', 'C', 'D'])) {
        $errors[] = "Invalid correct answer selection.";
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        try {
            // Find a quiz for the selected educator and topic
            $quizStmt = $pdo->prepare("
                SELECT id FROM Quiz 
                WHERE educatorID = ? AND topicID = ?
                LIMIT 1
            ");
            $quizStmt->execute([$_POST['educator_id'], $_POST['topic_id']]);
            $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$quiz) {
                // Store errors in session and redirect back
                $_SESSION['recommend_errors'] = ["The selected educator doesn't have a quiz for this topic yet. Please select a different educator or topic."];
                $_SESSION['form_data'] = $_POST;
                header("Location: recommend.php");
                exit;
            } else {
                // Handle file upload if provided (inline file upload handling)
                $questionFigureFileName = null;
                if (isset($_FILES['figure']) && $_FILES['figure']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['figure'];
                    
                    // Validate file is an image
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    if (!in_array($mimeType, $allowedTypes)) {
                        $errors[] = "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.";
                    }
                    
                    // Validate file size (max 5MB)
                    if ($file['size'] > 5 * 1024 * 1024) {
                        $errors[] = "File size too large. Maximum size is 5MB.";
                    }
                    
                    if (empty($errors)) {
                        // Create upload directory if it doesn't exist
                        if (!is_dir(UPLOAD_DIR_RECOMMENDED)) {
                            mkdir(UPLOAD_DIR_RECOMMENDED, 0755, true);
                        }
                        
                        // Generate unique filename
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = "recommended_" . $learnerId . '_' . time() . '_' . uniqid() . '.' . $extension;
                        $filepath = UPLOAD_DIR_RECOMMENDED . $filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $questionFigureFileName = $filename;
                        } else {
                            $errors[] = "Failed to save uploaded file.";
                        }
                    }
                }
                
                // If no file upload errors, proceed with database insertion
                if (empty($errors)) {
                    // Insert recommended question with status "Pending" (Requirement 14b)
                    $insertStmt = $pdo->prepare("
                        INSERT INTO RecommendedQuestion 
                        (quizID, learnerID, question, questionFigureFileName, 
                         answerA, answerB, answerC, answerD, correctAnswer, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    
                    $insertStmt->execute([
                        $quiz['id'],
                        $learnerId,
                        trim($_POST['question']),
                        $questionFigureFileName,
                        trim($_POST['answerA']),
                        trim($_POST['answerB']),
                        trim($_POST['answerC']),
                        trim($_POST['answerD']),
                        $_POST['correctAnswer']
                    ]);
                    
                    // Redirect to learner homepage on success (Requirement 14b)
                    header("Location: LearnerHomepage.php?success=Question+recommended+successfully");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $_SESSION['recommend_errors'] = ["Database error: " . $e->getMessage()];
            $_SESSION['form_data'] = $_POST;
            header("Location: recommend.php");
            exit;
        }
    }
    
    // If we have errors at this point, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['recommend_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: recommend.php");
        exit;
    }
} else {
    // If not POST, redirect to recommend page
    header("Location: recommend.php");
    exit;
}
?>