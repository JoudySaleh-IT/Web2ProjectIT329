<?php
session_start();
require "db_connect.php";
require "config.php";

/* Security check - must be logged in as learner */
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'learner') {
    header("Location: login.php?error=Please+log+in+as+a+learner");
    exit;
}

// Check if this is a valid quiz submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['quizID']) || empty($_POST['questionIDs'])) {
    header("Location: LearnerHomepage.php?error=Invalid+quiz+submission");
    exit;
}

$quizId = (int)$_POST['quizID'];
$learnerId = (int)$_SESSION['user_id'];
$questionIds = $_POST['questionIDs'];
$userAnswers = $_POST['answers'] ?? [];

/* Retrieve quiz details with educator information */
$quizSql = "
    SELECT 
        q.id AS quiz_id,
        t.topicName,
        u.firstName AS educator_first,
        u.lastName AS educator_last,
        u.photoFileName AS educator_photo
    FROM Quiz q
    JOIN Topic t ON t.id = q.topicID
    JOIN User u ON u.id = q.educatorID
    WHERE q.id = ?
";
$quizStmt = $pdo->prepare($quizSql);
$quizStmt->execute([$quizId]);
$quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    header("Location: LearnerHomepage.php?error=Quiz+not+found");
    exit;
}

/* Calculate score */
$correctCount = 0;
$totalQuestions = count($questionIds);

foreach ($questionIds as $questionId) {
    $questionId = (int)$questionId;
    $stmt = $pdo->prepare("SELECT correctAnswer FROM QuizQuestion WHERE id = ?");
    $stmt->execute([$questionId]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($question && isset($userAnswers[$questionId]) && $userAnswers[$questionId] === $question['correctAnswer']) {
        $correctCount++;
    }
}

$score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

/* Store the taken quiz record WITH learner ID */
try {
    // Check if TakenQuiz table has learnerID column
    $tableCheck = $pdo->query("SHOW COLUMNS FROM TakenQuiz LIKE 'learnerID'")->fetch();
    if ($tableCheck) {
        $takenQuizStmt = $pdo->prepare("INSERT INTO TakenQuiz (quizID, learnerID, score) VALUES (?, ?, ?)");
        $takenQuizStmt->execute([$quizId, $learnerId, $score]);
    } else {
        $takenQuizStmt = $pdo->prepare("INSERT INTO TakenQuiz (quizID, score) VALUES (?, ?)");
        $takenQuizStmt->execute([$quizId, $score]);
    }
} catch (PDOException $e) {
    // Log error but don't stop execution
    error_log("Error storing taken quiz: " . $e->getMessage());
}

/* Determine reaction video based on score */
if ($score >= 90) {
    $reactionVideo = 'Media/clapping.mp4';
    $videoPoster = 'Media/SC.png';
    $reactionMessage = "Outstanding! You're a quiz master!";
} elseif ($score >= 60) {
    $reactionVideo = 'Media/HappyStudent.mp4';
    $videoPoster = 'Media/SC.png';
    $reactionMessage = "Great job! You passed with flying colors!";
} else {
    $reactionVideo = 'Media/SadStudent.mp4';
    $videoPoster = 'Media/SC.png';
    $reactionMessage = "Don't worry! Keep practicing and try again!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Spark • Quiz Result & Feedback</title>

<style>
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
background:linear-gradient(90deg,#facc15,#f59e0b);color:#111827;border:none;padding:8px 14px;border-radius:10px;font-weight:700;cursor:pointer;
color: #111827;
padding: 8px 16px;
border-radius: 8px;
font-weight: 700;
text-decoration: none;
transition: background 0.2s;
}

.signout-btn:hover {
  background: #f59e0b;
}

  :root{
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

*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0; font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:var(--text);
  background:linear-gradient(180deg, rgba(34,211,238,.06) 0%, rgba(34,211,238,0) 40%, rgba(245,158,11,.06) 100%), var(--bg);
}

.wrap{max-width:1000px;margin:40px auto 80px;padding:0 20px}

.topbar{
  display:flex;justify-content:space-between;align-items:center;
  padding:10px 16px;margin-bottom:22px;
  background:linear-gradient(135deg,#fde047,#f59e0b);
  color:#0b0f19;border-radius:14px;box-shadow:var(--shadow)
}
.brand{font-weight:800}
.home-link{color:#0b0f19;text-decoration:none;font-weight:800}
.home-link:hover{text-decoration:underline}

.grid{
  display:grid;gap:20px;
  grid-template-columns:1fr;
}
@media (min-width:900px){
  .grid{grid-template-columns:1.1fr .9fr}
}

.card{
  background:var(--panel);border:1px solid var(--border);
  border-radius:var(--radius);box-shadow:var(--shadow)
}

/* Quiz Info Section */
.quiz-info {
  background: linear-gradient(135deg, #0f172a, #0b1224);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  margin-bottom: 20px;
}

.quiz-info h2 {
  margin: 0 0 16px 0;
  color: var(--accent);
  font-size: 1.4rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 0.85rem;
  color: var(--muted);
  font-weight: 600;
}

.info-value {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text);
}

.educator-photo {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--accent);
}

.score-card{padding:20px;display:grid;gap:18px}
.score-head{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap}
.score-head h1{margin:0;font-size:1.9rem;font-weight:900}
.score-chip{
  background:#0f1626;border:1px solid var(--border);
  padding:6px 10px;border-radius:999px;color:var(--muted);font-weight:700
}
.score-bar{
  height:12px;border-radius:999px;background:#0f1626;border:1px solid var(--border);
  overflow:hidden
}
.score-bar > span{
  display:block;height:100%;width:<?= $score ?>%;
  background:linear-gradient(90deg,var(--accent),var(--accentDeep))
}
.video-wrap{
  border:1px dashed #334155;border-radius:14px;overflow:hidden;background:#0b1220;
  position: relative;
}
.video-wrap video{display:block;width:100%;height:auto}
.reaction-message {
  position: absolute;
  bottom: 10px;
  left: 0;
  right: 0;
  text-align: center;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 8px;
  font-weight: 600;
}

.form-card{padding:20px}
.form-card h2{margin:0 0 8px;font-size:1.2rem;font-weight:900}
.muted{color:var(--muted);margin:0 0 16px}

.rate-row{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 12px}
.rate{
  flex:1; min-width:120px;
  background:#0f1626;border:1px solid var(--border);border-radius:12px;
  padding:12px
}
.rate label{display:block;font-weight:700;margin-bottom:8px;color:var(--text)}
.stars {
    display: flex;
    gap: 6px;
}

.stars input {
    display: none;
}

.stars label {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    cursor: pointer;
    background: #111c2a;
    border: 1px solid #233046;
    transition: all 0.2s ease;
    font-size: 18px;
    color: #e5e7eb;
}

.stars label:hover,
.stars label:hover ~ label {
    background: #1a2d4a;
    border-color: #facc15;
}

.stars input:checked + label,
.stars input:checked ~ input + label {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    color: #111827;
    border-color: #facc15;
    box-shadow: 0 0 12px rgba(250, 204, 21, 0.4);
}

.textarea{
  background:#0f1626;border:1px solid var(--border);border-radius:12px;
  padding:10px
}
.textarea textarea{
  width:100%;min-height:110px;resize:vertical;background:transparent;border:none;color:var(--text)
}
.textarea textarea::placeholder {
  color: #6b7280;
  font-style: italic;
}
.textarea textarea:focus {
  outline: none;
  border: 1px solid #facc15;
  box-shadow: 0 0 0 2px rgba(250, 204, 21, 0.1);
}

.form-actions{
  display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:14px
}
.btn{
  border:none;border-radius:12px;padding:10px 16px;font-weight:800;cursor:pointer
}
.btn.primary{background:linear-gradient(135deg,var(--accent),var(--accentDeep));color:#111}
.btn.ghost{
  background:transparent;border:1px solid var(--border);color:var(--text);text-decoration:none;display:inline-block
}
.btn:active{transform:translateY(1px)}

.kpi .val{color:var(--text);font-weight:800}

/*FOOTER*/
.spark-footer {
background: linear-gradient(90deg, #0b0f19, #071123,#142645 );
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

@media (max-width:800px){
  .info{grid-template-columns:1fr}
  .info .avatar{justify-self:start}
  thead{display:none}
  table, tbody, tr, td{display:block;width:100%}
  tbody tr{padding:12px 10px}
  tbody td{border:none;padding:8px 0}
  tbody td::before{
    content:attr(data-label);display:block;color:var(--muted);font-size:.85rem;margin-bottom:6px
  }
  .wrap{margin:60px auto}
}
</style>
</head>
<body>

<!-- ====== HEADER ====== -->
<header class="spark-header">
  <div class="header-inner">
    <div class="header-left">
      <a href="LearnerHomepage.php">
        <img src="Media/sparklogo.png" alt="Spark Logo" class="header-logo">
      </a>
    </div>
    <div class="header-right">
      <a href="logout.php" class="signout-btn">Sign Out</a>
    </div>
  </div>
</header>

<div class="wrap">
    <div class="topbar">
      <div class="brand">Spark — Quiz Result</div>
      <a href="LearnerHomepage.php" class="home-link">Back to Learner Home</a>
    </div>

    <!-- Quiz Information Section -->
    <section class="quiz-info">
        <h2>Quiz Details</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Topic</span>
                <span class="info-value"><?= htmlspecialchars($quiz['topicName']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Educator</span>
                <span class="info-value"><?= htmlspecialchars($quiz['educator_first'] . ' ' . $quiz['educator_last']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Questions</span>
                <span class="info-value"><?= $totalQuestions ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Correct Answers</span>
                <span class="info-value"><?= $correctCount ?></span>
            </div>
        </div>
    </section>

    <div class="grid">
      <!-- PART 1: Score + Reaction Video -->
      <section class="card score-card" aria-labelledby="scoreTitle">
        <div class="score-head">
          <h1 id="scoreTitle">Your score: <span class="val"><?= $score ?>%</span></h1>
          <span class="score-chip">Topic: <?= htmlspecialchars($quiz['topicName']) ?></span>
          <span class="score-chip">Questions: <?= $totalQuestions ?></span>
          <span class="score-chip">Correct: <?= $correctCount ?></span>
        </div>

        <div class="score-bar" aria-label="Score progress">
          <span style="width:<?= $score ?>%"></span>
        </div>

        <!-- Autoplay reaction video -->
        <div class="video-wrap" aria-label="Reaction video">
          <video autoplay muted playsinline loop poster="<?= $videoPoster ?>">
            <source src="<?= $reactionVideo ?>" type="video/mp4" />
            Your browser does not support the video tag.
          </video>
          <div class="reaction-message"><?= $reactionMessage ?></div>
        </div>
        <p class="muted">Tip: Videos are muted by default so they can autoplay smoothly.</p>
      </section>

      <!-- PART 2: Optional Feedback Form -->
      <section class="card form-card" aria-labelledby="feedbackTitle">
        <h2 id="feedbackTitle">Share your feedback (optional)</h2>
        <p class="muted">Rate this quiz and leave a short comment. Your input helps us improve!</p>

        <form action="submit_feedback.php" method="post">
          <input type="hidden" name="quizID" value="<?= $quizId ?>">
          
          <!-- Rating -->
          <div class="rate-row">
            <div class="rate" role="group" aria-labelledby="rateLabel">
                <label id="rateLabel">Rating</label>
                <div class="stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>">
                        <label for="star<?= $i ?>" title="<?= $i ?>★">★</label>
                    <?php endfor; ?>
                </div>
            </div>
          </div>
          
          <!-- Comments -->
          <div class="textarea">
            <textarea name="comments" placeholder="Write an optional comment..."></textarea>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <button type="submit" class="btn primary">Submit feedback</button>
            <a href="LearnerHomepage.php" class="btn ghost">Return without submitting</a>
          </div>
        </form>
      </section>
    </div>
  </div>

<footer class="spark-footer">
  <div class="footer-top">
    <div class="footer-left">
      <a href="LearnerHomepage.php">
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const scoreEl = document.querySelector('.score-head .val');
    const video   = document.querySelector('.video-wrap video');
    const bar     = document.querySelector('.score-bar > span');

    if (scoreEl && video) {
        const raw = scoreEl.textContent.trim().replace('%','');
        const score = Math.max(0, Math.min(100, parseInt(raw, 10) || 0));

        const pick = s => s >= 90 ? {src:'Media/clapping.mp4',  poster:'Media/SC.png', message:"Outstanding! You're a quiz master!"}
                        : s >= 60 ? {src:'Media/HappyStudent.mp4', poster:'Media/SC.png', message:"Great job! You passed with flying colors!"}
                                  : {src:'Media/SadStudent.mp4', poster:'Media/SC.png', message:"Don't worry! Keep practicing and try again!"};

        const {src, poster, message} = pick(score);

        if (bar) bar.style.width = score + '%';

        video.pause();
        if (poster) video.setAttribute('poster', poster);
        if (video.getAttribute('src') !== src) video.setAttribute('src', src);
        
        // Update reaction message
        const messageEl = document.querySelector('.reaction-message');
        if (messageEl) messageEl.textContent = message;
        
        video.load();
        video.play().catch(()=>{/* ignored if autoplay blocked */});
    }
});

// ==== ADD THE NEW CODE RIGHT HERE ====
// Prevent empty feedback submission
document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.querySelector('form[action="submit_feedback.php"]');
    const submitBtn = feedbackForm?.querySelector('button[type="submit"]');
    
    if (feedbackForm && submitBtn) {
        // Function to check if form has content
        function hasFeedbackContent() {
            const rating = document.querySelector('input[name="rating"]:checked');
            const comments = document.querySelector('textarea[name="comments"]').value.trim();
            return !!rating || !!comments;
        }
        
        // Update button state initially
        function updateSubmitButton() {
            submitBtn.disabled = !hasFeedbackContent();
        }
        
        // Check on page load
        updateSubmitButton();
        
        // Check when user interacts with form
        feedbackForm.addEventListener('change', updateSubmitButton);
        feedbackForm.addEventListener('input', updateSubmitButton);
        
        // Add visual styling for disabled button
        const style = document.createElement('style');
        style.textContent = `
            button[type="submit"]:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
            }
        `;
        document.head.appendChild(style);
        
        // Prevent form submission if empty
        feedbackForm.addEventListener('submit', function(e) {
            if (!hasFeedbackContent()) {
                e.preventDefault();
                alert('Please provide a rating or comment before submitting feedback.');
                return false;
            }
        });
    }
});
</script>
</body>
</html>