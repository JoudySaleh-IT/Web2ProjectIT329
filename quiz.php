<?php
session_start();
require "db_connect.php";

// Security check - ensure user is logged in
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    header("Location: login.php?error=Please+log+in+as+an+educator");
    exit;
}

// Get quiz ID from request
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quizId === 0) {
    die("Invalid quiz ID");
}

// Get quiz details (topic and educator info)
$quizStmt = $pdo->prepare("
    SELECT t.topicName, u.firstName, u.lastName 
    FROM Quiz q 
    JOIN Topic t ON q.topicID = t.id 
    JOIN User u ON q.educatorID = u.id 
    WHERE q.id = ?
");
$quizStmt->execute([$quizId]);
$quiz = $quizStmt->fetch();

if (!$quiz) {
    die("Quiz not found");
}

// Get all questions for this quiz
$questionsStmt = $pdo->prepare("
    SELECT id, question, questionFigureFileName, answerA, answerB, answerC, answerD, correctAnswer 
    FROM QuizQuestion 
    WHERE quizID = ? 
    ORDER BY id ASC
");
$questionsStmt->execute([$quizId]);
$questions = $questionsStmt->fetchAll();

$topicName = $quiz['topicName'];
$educatorName = $quiz['firstName'] . ' ' . $quiz['lastName'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Spark • <?= htmlspecialchars($topicName) ?> Quiz</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <!-- Add jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
   :root{
  --bg:#0b0f19; --card:#0f172a; --ring:#334155; --text:#e5e7eb;
  --muted:#9aa3b2; --warn:#fde047;
  --accent:#facc15; --accentDeep:#f59e0b;
  --success:#10b981; --danger:#ef4444;
}

/* Reset & base */
*{box-sizing:border-box}
    body{
      margin:0; font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:var(--text);
      background:linear-gradient(180deg, rgba(34,211,238,.06) 0%, rgba(34,211,238,0) 40%, rgba(245,158,11,.06) 100%), var(--bg);
    }
    a{color:inherit; text-decoration:none}
    .container{max-width:1100px; margin:0 auto; padding:24px}
a{color:inherit; text-decoration:none}
.container{max-width:1100px; margin:0 auto; padding:24px}

/* ====== HEADER STYLES ====== */
.spark-header {
  background:none;
  color:#e5e7eb;
  padding:10px 20px;
  top:0;
  z-index:1000;
  box-shadow:0 2px 10px rgba(0,0,0,0.25);
}
.header-inner {
  max-width:1100px;
  margin:0 auto;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
.header-left{display:flex; align-items:center;}
.header-logo{width:150px; height:auto;}
.header-title{font-weight:700; font-size:18px; color:#facc15;}
.signout-btn {
background:linear-gradient(90deg,#facc15,#f59e0b);color:#111827;border:none;padding:8px 14px;border-radius:10px;font-weight:700;cursor:pointer;
color: #111827;
padding: 8px 16px;
border-radius: 8px;
font-weight: 700;
text-decoration: none;
transition: background 0.2s;
}
/* ====== BUTTONS ====== */
.btn{
  border:none;
  border-radius:12px;
  padding:10px 14px;
  font-weight:800;
  cursor:pointer;
  text-decoration:none;
}
.btn.primary{
  background:linear-gradient(90deg,var(--accent),var(--accentDeep));
  color:#111827;
  border:0;
}
.btn:active{transform:translateY(1px);}

/* ====== PAGE HEADER ====== */
.topbar{display:flex; justify-content:space-between; align-items:center; gap:12px; margin:18px 0}
.topbar h1{
  background:linear-gradient(90deg,#fde047,#facc15,#f97316,#ef4444);
  -webkit-background-clip:text;
  background-clip:text;
  color:transparent;
  -webkit-text-fill-color:transparent;
  margin:0; font-size:28px; letter-spacing:-.2px;
}

/* ====== CARDS ====== */
.grid{display:grid; grid-template-columns:1fr; gap:14px}
.card{
  background:var(--card);
  border:1px solid var(--ring);
  border-radius:14px;
  padding:16px;
  box-shadow:0 10px 28px rgba(0,0,0,.25);
}

/* ====== QUESTION ====== */
.question{display:flex; flex-direction:column; gap:12px}
.q-figure{
  width:100%; max-width:300px; height:180px;
  border-radius:12px; overflow:hidden;
  background:#0b1222; border:1px solid var(--ring);
  display:flex; align-items:center; justify-content:center;
  color:var(--muted); font-size:12px;
}
.q-figure img{width:100%; height:100%; object-fit:cover}
.q-text{font-weight:700; margin:0 0 8px}
.options{list-style:none; padding:0; margin:0; display:grid; gap:8px}
.option{
  border:1px solid var(--ring);
  border-radius:10px;
  padding:10px 12px;
  background:#0b1222;
}
.option.correct{border-color:var(--success); background:rgba(16,185,129,.08)}

/* ====== CARD FOOTER ====== */
.card-footer{
  display:flex; justify-content:space-between; align-items:center;
  gap:12px; margin-top:12px; padding-top:12px;
  border-top:1px solid var(--ring);
}
.links{display:flex; gap:16px}
.links a{color:var(--accent)}
.links a.delete{color:var(--danger); font-weight:700; cursor:pointer;}

/* ====== BACK TO HOME BUTTON ====== */
.back-home-container {
  display: flex;
  justify-content: flex-start;
  margin: 30px 0 20px 0;
}

.home-btn {
  background: transparent;
  color: var(--muted);
  border: 1px solid var(--ring);
  padding: 10px 16px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s ease;
}

.home-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text);
  border-color: var(--accent);
}

/* ====== FOOTER ====== */
.spark-footer {
  background:linear-gradient(90deg,#0b0f19,#071123,#142645);
  color:#e5e7eb;
  padding:20px 16px 10px;
  font-family:system-ui,-apple-system, Segoe UI, Roboto, Ubuntu, Arial;
  border-top:1px solid;
  border-image:linear-gradient(to right,#cc6d01,#facc15) 1;
}
.footer-top{
  max-width:1100px; margin:0 auto;
  display:flex; justify-content:space-between; align-items:center;
}
.footer-left .footer-logo{width:150px; height:auto;}
.footer-right{display:flex; gap:16px;}
.footer-right .social{
  display:inline-flex; align-items:center; justify-content:center;
  width:36px; height:36px; border-radius:50%;
  background:rgba(255,255,255,0.08);
  color:#e5e7eb;
  text-decoration:none;
  transition:background 0.2s, color 0.2s;
}
.footer-right .social:hover{background:#facc15; color:#111827;}
.footer-bottom{text-align:center; font-size:14px; margin-top:14px; color:#9ca3af;}

.empty-message {
  text-align: center;
  color: var(--muted);
  font-style: italic;
  padding: 40px;
  background: var(--card);
  border-radius: 14px;
  border: 1px solid var(--ring);
}

/* Delete confirmation and status messages */
.delete-confirm {
  background: var(--danger);
  color: white;
  padding: 10px;
  border-radius: 8px;
  margin: 10px 0;
  text-align: center;
}

  </style>
</head>
<body>

 <!-- ====== HEADER ====== -->
<header class="spark-header">
<div class="header-inner">
<!-- Logo placeholder -->
<div class="header-left">
<a href="EducatorHomepage.php">
<img src="Media/sparklogo.png" alt="Spark Logo" class="header-logo">
</a>
</div>
<!-- Sign out button -->
<div class="header-right">
<a href="logout.php" class="signout-btn">Sign Out</a>
</div>
</div>
</header>
 
  <main class="container">
    <div class="topbar">
      <h1><?= htmlspecialchars($topicName) ?> Quiz</h1>
      <a class="btn primary" href="addQ.php?quiz_id=<?= $quizId ?>">Add New Question</a>
    </div>

    <section class="grid" aria-label="All questions in this quiz" id="questions-container">
      <?php if (empty($questions)): ?>
        <div class="empty-message">
          This quiz doesn't have any questions yet. <a href="addQ.php?quiz_id=<?= $quizId ?>" style="color: var(--accent);">Add the first question</a>.
        </div>
      <?php else: ?>
        <?php foreach ($questions as $index => $q): 
          $questionId = $q['id'];
          $questionText = $q['question'];
          $figureFile = $q['questionFigureFileName'];
          $answers = [
            'A' => $q['answerA'],
            'B' => $q['answerB'], 
            'C' => $q['answerC'],
            'D' => $q['answerD']
          ];
          $correctAnswer = $q['correctAnswer'];
        ?>
          <article class="card" id="question-<?= $questionId ?>">
            <div class="question">
              <?php if ($figureFile): ?>
                <figure class="q-figure">
                  <img src="uploads/questions/<?= htmlspecialchars($figureFile) ?>" alt="Question Figure">
                </figure>
              <?php else: ?>
                <figure class="q-figure">No Figure</figure>
              <?php endif; ?>
              <p class="q-text"><?= htmlspecialchars($questionText) ?></p>
              <ul class="options">
                <?php foreach ($answers as $letter => $answerText): 
                  $isCorrect = ($letter === $correctAnswer);
                ?>
                  <li class="option <?= $isCorrect ? 'correct' : '' ?>">
                    <?= htmlspecialchars($letter) ?>) <?= htmlspecialchars($answerText) ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="card-footer">
              <div class="links">
                <a href="editQ.php?id=<?= $questionId ?>">Edit</a>
                <a class="delete" href="#" data-question-id="<?= $questionId ?>" data-quiz-id="<?= $quizId ?>">Delete</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <!-- Back to Homepage Button -->
    <div class="back-home-container">
      <a href="EducatorHomepage.php" class="home-btn">← Back to Homepage</a>
    </div>
  </main>

 <!-- ====== FOOTER ====== -->
<footer class="spark-footer">
<div class="footer-top">
<!-- Left: Logo -->
<div class="footer-left">
<a href="EducatorHomepage.php">
<img src="Media/sparklogo.png" alt="Spark Logo" class="footer-logo">
</a>
</div>
<!-- Right: Contact Info -->
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
<!-- Bottom: Copyright -->
<div class="footer-bottom">
<p>© 2025 Spark. All Rights Reserved.</p>
</div>
</footer>

<script>
$(document).ready(function() {
    // Handle delete button clicks
    $(document).on('click', 'a.delete', function(e) {
        e.preventDefault();
        
        const questionId = $(this).data('question-id');
        const quizId = $(this).data('quiz-id');
        const questionElement = $(this).closest('.card');
        
        // Confirm deletion
        if (confirm('Are you sure you want to delete this question?')) {
            // Send AJAX request
            $.ajax({
                url: 'delete_question.php',
                type: 'POST',
                data: {
                    id: questionId,
                    quiz_id: quizId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success === true) {
                        // Remove the question row from HTML table
                        questionElement.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if no questions left
                            if ($('#questions-container .card').length === 0) {
                                $('#questions-container').html(
                                    '<div class="empty-message">This quiz doesn\'t have any questions yet. <a href="addQ.php?quiz_id=' + quizId + '" style="color: var(--accent);">Add the first question</a>.</div>'
                                );
                            }
                        });
                    } else {
                        alert('Failed to delete question: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error: Could not connect to server. Please try again.');
                }
            });
        }
    });
});
</script>
 
</body>
</html>