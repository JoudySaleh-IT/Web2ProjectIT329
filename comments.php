<?php
session_start();
require __DIR__ . '/db_connect.php';

// Security check - redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get quiz ID from request
$quizID = $_GET['quizID'] ?? $_POST['quizID'] ?? null;

if (!$quizID) {
    header("Location: EducatorHomepage.php?error=No+quiz+selected");
    exit;
}

// Get quiz details and feedback from database
try {
    // Get quiz topic and educator info
    $quizStmt = $pdo->prepare("
        SELECT t.topicName, u.firstName, u.lastName 
        FROM Quiz q 
        JOIN Topic t ON q.topicID = t.id 
        JOIN User u ON q.educatorID = u.id 
        WHERE q.id = ?
    ");
    $quizStmt->execute([$quizID]);
    $quiz = $quizStmt->fetch();

    if (!$quiz) {
        header("Location: EducatorHomepage.php?error=Quiz+not+found");
        exit;
    }

    // Get all feedback for this quiz, ordered by newest first
    $feedbackStmt = $pdo->prepare("
        SELECT comments, date, rating 
        FROM QuizFeedback 
        WHERE quizID = ? 
        ORDER BY date DESC
    ");
    $feedbackStmt->execute([$quizID]);
    $feedbacks = $feedbackStmt->fetchAll();

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Spark • Comments</title>

  <style>
    :root{
        --layout-width:1100px; 
        --header-h:92px;    
        --bg:#0b0f19;
        --panel:#111827;
        --border:#1f2937;
        --text:#e5e7eb;
        --muted:#9ca3af;
        --accent:#facc15;   
        --accentDeep:#f59e0b;
        --ok:#34d399;
        --radius:16px;
        --shadow:0 10px 28px rgba(0,0,0,.28);
    }
    
    .main-content { 
        max-width:1100px; 
        margin:90px auto; 
        padding:0 20px; 
    }

    *{box-sizing:border-box}
    
    body{
        margin:0; 
        font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; 
        color:var(--text);
        background:linear-gradient(180deg, rgba(34,211,238,.06) 0%, rgba(34,211,238,0) 40%, rgba(245,158,11,.06) 100%), var(--bg);
    }

    /* ====== HEADER STYLES ====== */
    .spark-header {
        background: none;
        color: #e5e7eb;
        padding: 10px 20px;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.25);
    }
    
    .header-inner {
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .header-left {
        display: flex;
        align-items: center;
    }
    
    .header-logo {
        width: 150px;
        height: auto;
    }
    
    .header-title {
        font-weight: 700;
        font-size: 18px;
        color: #facc15;
    }
    
    .signout-btn {
        background:linear-gradient(90deg,#facc15,#f59e0b);
        color:#111827;
        border:none;
        padding:8px 14px;
        border-radius:10px;
        font-weight:700;
        cursor:pointer;
        text-decoration: none;
        transition: background 0.2s;
    }

    h1{
        background:linear-gradient(90deg,#fde047,#facc15,#f97316,#ef4444);
        -webkit-background-clip:text; 
        background-clip:text;
        color:transparent; 
        -webkit-text-fill-color:transparent;
        padding-top:10px; 
        padding-bottom:10px; 
        text-align:center;
    }

    .home-link{
        color:#0b0f19;
        text-decoration:none;
        font-weight:800
    }
    
    .home-link:hover{
        text-decoration:underline
    }

    .quiz-meta{
        margin:20px;
        color:rgba(229,231,235,.72);
        font-size:.95rem;
    }
    
    .page-actions{
        margin-top:18px;
        text-align:right;
    }
    
    .btn{
        background:#facc15;
        color:#111827;
        border:none;
        padding:8px 14px;
        border-radius:10px;
        font-weight:700;
    }
    
    .comments-section{
        margin-top:14px;
    }
    
    .comment-list{
        list-style:none;
        padding:0;
        margin:0;
    }
    
    .comment-card{
        background:#111827;
        border:1px solid rgba(229,231,235,.12);
        padding:14px; 
        margin:16px 0;
    }
    
    .comment-text{
        line-height:1.5;
        margin-bottom:10px;
        color:#e5e7eb;
    }
    
    .comment-meta{
        display:flex;
        justify-content:space-between;
        font-size:.9rem;
        color:rgba(229,231,235,.72);
    }

    /*BUTTONS*/
    .btn{
        border:none;
        border-radius:12px;
        padding:10px 14px;
        font-weight:800;
        cursor:pointer;
        text-decoration:none;
    }
    
    .btn-ghost{
        background:transparent;
        color:var(--muted);
        border:1px solid rgba(229,231,235,.12);
    }
    
    .btn-ghost:hover{
        color:var(--text);
        border-color:rgba(250,204,21,.6);
    }

    .no-comments {
        text-align: center;
        color: var(--muted);
        font-style: italic;
        padding: 40px;
        background: var(--panel);
        border-radius: var(--radius);
        border: 1px solid var(--border);
    }

    /* Footer styles */
    .spark-footer {
        background: linear-gradient(90deg, #0b0f19, #071123,#142645);
        color: #e5e7eb;
        padding: 20px 16px 10px;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial;
        border-top: 1px solid;
        border-image: linear-gradient(to right, #cc6d01, #facc15) 1;
    }
    
    .footer-top {
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .footer-left .footer-logo {
        width: 150px;
        height: auto;
    }
    
    .footer-right {
        display: flex;
        gap: 16px;
    }
    
    .footer-right .social {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        color: #e5e7eb;
        text-decoration: none;
        transition: background 0.2s, color 0.2s;
    }
    
    .footer-right .social:hover {
        background: #facc15;
        color: #111827;
    }
    
    .footer-bottom {
        text-align: center;
        font-size: 14px;
        margin-top: 14px;
        color: #9ca3af;
    }
  </style>
</head>

<body>
<!-- ====== HEADER ====== -->
<header class="spark-header">
    <div class="header-inner">
        <div class="header-left">
            <a href="<?php echo $_SESSION['user_type'] === 'educator' ? 'EducatorHomepage.php' : 'LearnerHomepage.php'; ?>">
                <img src="Media/sparklogo.png" alt="Spark Logo" class="header-logo">
            </a>
        </div>
        <div class="header-right">
            <a href="logout.php" class="signout-btn">Sign Out</a>
        </div>
    </div>
</header>

<main class="main-content">
    <!-- top bar -->
    <div class="topbar">
        <h1>Comments</h1>
    </div>
    
    <header class="page-header">
        <p class="quiz-meta">
            <span><strong>Topic:</strong> <?php echo htmlspecialchars($quiz['topicName']); ?></span>
            <span><strong>Educator:</strong> <?php echo htmlspecialchars($quiz['firstName'] . ' ' . $quiz['lastName']); ?></span>
        </p>
    </header>
    
    <section class="comments-section">
        <?php if (empty($feedbacks)): ?>
            <div class="no-comments">
                No comments yet for this quiz.
            </div>
        <?php else: ?>
            <ul id="commentList" class="comment-list">
                <?php foreach ($feedbacks as $feedback): ?>
                    <li class="comment-card">
                        <div class="comment-text"><?php echo htmlspecialchars($feedback['comments']); ?></div>
                        <div class="comment-meta">
                            <span>Anonymous</span>
                            <time><?php echo date('M j, Y • H:i', strtotime($feedback['date'])); ?></time>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    
    <div class="page-actions">
        <a class="btn btn-ghost" href="<?php echo $_SESSION['user_type'] === 'educator' ? 'EducatorHomepage.php' : 'LearnerHomepage.php'; ?>">← Back</a>
    </div>
</main>

<!-- ====== FOOTER ====== -->
<footer class="spark-footer">
    <div class="footer-top">
        <div class="footer-left">
            <a href="index.php">
                <img src="Media/sparklogo.png" alt="Spark Logo" class="footer-logo">
            </a>
        </div>
        <div class="footer-right">
            <a href="#" aria-label="Instagram" class="social">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm5 5a5 5 0 100 10 5 5 0 000-10zm6-.8a1 1 0 100 2 1 1 0 000-2z"/></svg>
            </a>
            <a href="#" aria-label="X" class="social">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M19.6 2.3h2.4l-5.2 6 6.1 8h-4.8l-3.6-4.8-4.1 4.8H3.8l5.6-6.4L3.6 2.3H8l3.2 4.4z"/></svg>
            </a>
            <a href="mailto:info@spark.com" aria-label="Email" class="social">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6h16a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2zm0 2l8 5 8-5"/></svg>
            </a>
            <a href="tel:+966555555555" aria-label="Phone" class="social">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M6.6 10.8a15 15 0 006.6 6.6l2.2-2.2a1 1 0 011.1-.2c1.2.5 2.5.8 3.9.8a1 1 0 011 1V20a1 1 0 01-1 1C12.4 21 3 11.6 3 1a1 1 0 011-1h3.2a1 1 0 011 1c0 1.4.3 2.7.8 3.9.1.4 0 .8-.2 1.1l-2.2 2.2z"/></svg>
            </a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2025 Spark. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>