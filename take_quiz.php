<?php
session_start();
require "db_connect.php";
require "config.php";

// Check if user is logged in and is a learner
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'learner') {
    header("Location: login.php?error=Please+log+in+as+a+learner");
    exit;
}

try {
    // Check if quiz ID is provided in query string
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        die("Quiz ID is required");
    }
    
    $quizID = $_GET['id'];
    
    // Retrieve quiz details with topic and educator info
    $quizQuery = "
        SELECT q.id, t.topicName, u.firstName, u.lastName, u.photoFileName 
        FROM Quiz q 
        JOIN Topic t ON q.topicID = t.id 
        JOIN User u ON q.educatorID = u.id 
        WHERE q.id = ?
    ";
    $stmt = $pdo->prepare($quizQuery);
    $stmt->execute([$quizID]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        die("Quiz not found");
    }
    
    // Retrieve all questions for this quiz
    $questionsQuery = "
        SELECT id, question, questionFigureFileName, answerA, answerB, answerC, answerD 
        FROM QuizQuestion 
        WHERE quizID = ?
    ";
    $stmt = $pdo->prepare($questionsQuery);
    $stmt->execute([$quizID]);
    $allQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allQuestions)) {
        header("Location: LearnerHomepage.php?error=No+questions+available+for+this+quiz");
        exit;
    }
    
    // Select 5 random questions (or all if less than 5)
    $totalQuestions = count($allQuestions);
    $selectedQuestions = $allQuestions;
    
    if ($totalQuestions > 5) {
        shuffle($allQuestions);
        $selectedQuestions = array_slice($allQuestions, 0, 5);
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Spark • Take Quiz</title>
<style>
  :root{
  --bg:#0b0f19; --card:#0f172a; --ring:#334155; --text:#e5e7eb;
  --muted:#9aa3b2; --warn:#fde047;
  --accent:#facc15; --accentDeep:#f59e0b;
  --danger:#ef4444;
}

*{box-sizing:border-box}
    body{
      margin:0; font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:var(--text);
      background:linear-gradient(180deg, rgba(34,211,238,.06) 0%, rgba(34,211,238,0) 40%, rgba(245,158,11,.06) 100%), var(--bg);
    }
    a{color:inherit; text-decoration:none}
    .container{max-width:1100px; margin:0 auto; padding:24px}



/* ====== HEADER ====== */
.spark-header{
  background:none;
  color:#e5e7eb;
  padding:10px 20px;
  top:0;
  z-index:1000;
  box-shadow:0 2px 10px rgba(0,0,0,0.25);
}
.header-inner{
  max-width:1100px; margin:0 auto;
  display:flex; justify-content:space-between; align-items:center;
}
.header-left{ display:flex; align-items:center; gap:12px; }
.header-logo{ width:150px; height:auto; }     
.header-title{ font-weight:700; font-size:18px; color:#facc15; }
.signout-btn {
background:linear-gradient(90deg,#facc15,#f59e0b);color:#111827;border:none;padding:8px 14px;border-radius:10px;font-weight:700;cursor:pointer;
color: #111827;
padding: 8px 16px;
border-radius: 8px;
font-weight: 700;
text-decoration: none;
transition: background 0.2s;
}


/* topic container */
.wrap, .main-content{ max-width:900px; margin:90px auto 60px; padding:0 16px; }

/* topic */
h1, .topic-title{
  background:linear-gradient(90deg,#fde047,#facc15,#f97316,#ef4444);
  -webkit-background-clip:text; background-clip:text;
  color:transparent; -webkit-text-fill-color:transparent;
  text-align:center; padding-top:20px; padding-bottom:20px;
}

/* Educator Info (teacher card) */
.meta { display:block; margin-bottom:18px; }
.who {
  display:flex; align-items:center; gap:14px;
  background:linear-gradient(180deg, #0f172a, #0b1224);
  border:1px solid var(--ring); border-radius:16px; padding:14px 16px; width:100%;
}
.who img {
  width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid #1f2a44;
}
.who .t { font-size:14px; color:var(--muted); margin-bottom:2px; }
.who .h { font-weight:700; }

/* cards */
.card{
  background:var(--card); border:1px solid var(--ring);
  border-radius:14px; box-shadow:0 10px 28px rgba(0,0,0,.25);
  overflow:hidden;
}
.card-head, .card-header{
  padding:16px 18px; border-bottom:1px solid var(--ring); font-weight:700;
}
.card-body{ padding:18px; }

/* quiz questions */
.q{ border:1px solid #1d273d; background:#0c1326; border-radius:14px; padding:16px; margin:14px 0; }
.q-header{ display:flex; align-items:flex-start; gap:12px; margin-bottom:10px; }
.q-index{ font-weight:700; }
.q-text{ font-size:16px; line-height:1.5; text-align:left; }
.opts{ display:grid; gap:10px; margin-top:8px; text-align:left; }
.opt{ display:flex; align-items:flex-start; gap:10px; border:1px solid #1d273d; background:#0b1224; border-radius:12px; padding:12px; }
.opt:hover{ border-color:#2a395a; }
.opt input{ margin-top:2px; }
.opt label{ cursor:pointer; display:block; }

/* question images */
.q-image {
  max-width: 300px;
  max-height: 200px;
  border-radius: 8px;
  margin: 10px 0;
  border: 1px solid var(--ring);
}

/* highlight missing question */
.q.missing{
  border-color: var(--danger);
  box-shadow: inset 0 0 0 1px rgba(239,68,68,.45);
}

/* lower footer */
.actions, .actions-add{
  display:flex; align-items:center; justify-content:space-between; gap:12px;
  padding:14px 18px; border-top:1px solid var(--ring); background:#0c1326;
  border-radius:0 0 14px 14px;
}

/* buttons*/
.btn{
  appearance:none; border:1px solid rgba(229,231,235,.12);
  background:transparent; color:#e5e7eb;
  padding:10px 16px; border-radius:12px; font-weight:700; cursor:pointer; text-decoration:none;
}
.btn-primary, .btn.primary, .btn-Add{
  background:linear-gradient(90deg,var(--accent),var(--accentDeep)) !important;
  color:#111827 !important; border:0 !important;
}
.btn-ghost{ background:transparent; color:var(--muted); }

/* helper */
.req{ color:var(--muted); font-size:14px; }
.sr{ position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }

/* ====== FOOTER ====== */
.spark-footer{
  background:linear-gradient(90deg,#0b0f19,#071123,#142645);
  color:#e5e7eb; padding:20px 16px 10px;
  font-family:system-ui,-apple-system, Segoe UI, Roboto, Ubuntu, Arial;
  border-top:1px solid; border-image:linear-gradient(to right,#cc6d01,#facc15) 1;
}
.footer-top{
  max-width:1100px; margin:0 auto;
  display:flex; justify-content:space-between; align-items:center;
}
.footer-left .footer-logo{ width:150px; height:auto; }
.footer-right{ display:flex; gap:16px; }
.footer-right .social{
  display:inline-flex; align-items:center; justify-content:center;
  width:36px; height:36px; border-radius:50%;
  background:rgba(255,255,255,0.08); color:#e5e7eb;
  text-decoration:none; transition:background .2s, color .2s;
}
.footer-right .social:hover{ background:#facc15; color:#111827; }
.footer-bottom{ text-align:center; font-size:14px; margin-top:14px; color:#9ca3af; }

/* mobile */
@media (max-width:640px){
  .form-grid{ grid-template-columns:1fr; }
  .label{ padding-top:0; }
  .label.top{ padding-top:0; }
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

<main class="wrap">
  <h1 class="topic-title"><?php echo htmlspecialchars($quiz['topicName']); ?> Quiz</h1>

  <!-- Educator info -->
  <section class="meta">
    <div class="who">
      <img alt="Educator photo" src="<?php echo UPLOAD_URL_USERS . ($quiz['photoFileName'] ? htmlspecialchars($quiz['photoFileName']) : DEFAULT_USER_PHOTO); ?>">
      <div>
        <div class="t" style="margin-top:6px">Educator</div>
        <div class="h"><?php echo htmlspecialchars($quiz['firstName'] . ' ' . $quiz['lastName']); ?></div>
      </div>
    </div>
  </section>

  <!-- Dynamic quiz card -->
  <section class="card">
    <div class="card-header">
      <div class="title">Answer all questions</div>
    </div>

    <form id="quizForm" action="quiz_score.php" method="post">
      <!-- Hidden inputs for quiz ID and question IDs -->
      <input type="hidden" name="quizID" value="<?php echo $quizID; ?>">
      <?php foreach ($selectedQuestions as $index => $question): ?>
        <input type="hidden" name="questionIDs[]" value="<?php echo $question['id']; ?>">
      <?php endforeach; ?>
      
      <div class="card-body">
        <?php foreach ($selectedQuestions as $index => $question): ?>
          <fieldset class="q" data-group="q<?php echo $index + 1; ?>">
            <legend class="sr">Question <?php echo $index + 1; ?></legend>
            <div class="q-header">
              <div class="q-index">Q<?php echo $index + 1; ?></div>
              <div class="q-text"><?php echo htmlspecialchars($question['question']); ?></div>
            </div>
            
            <?php if ($question['questionFigureFileName']): ?>
              <div class="q-image-container">
                <img src="<?php echo UPLOAD_URL_QUESTIONS . htmlspecialchars($question['questionFigureFileName']); ?>" 
                     alt="Question figure" class="q-image">
              </div>
            <?php endif; ?>
            
            <div class="opts">
              <div class="opt">
                <input type="radio" id="q<?php echo $index + 1; ?>a" name="answers[<?php echo $question['id']; ?>]" value="A" required>
                <label for="q<?php echo $index + 1; ?>a">A. <?php echo htmlspecialchars($question['answerA']); ?></label>
              </div>
              <div class="opt">
                <input type="radio" id="q<?php echo $index + 1; ?>b" name="answers[<?php echo $question['id']; ?>]" value="B">
                <label for="q<?php echo $index + 1; ?>b">B. <?php echo htmlspecialchars($question['answerB']); ?></label>
              </div>
              <div class="opt">
                <input type="radio" id="q<?php echo $index + 1; ?>c" name="answers[<?php echo $question['id']; ?>]" value="C">
                <label for="q<?php echo $index + 1; ?>c">C. <?php echo htmlspecialchars($question['answerC']); ?></label>
              </div>
              <div class="opt">
                <input type="radio" id="q<?php echo $index + 1; ?>d" name="answers[<?php echo $question['id']; ?>]" value="D">
                <label for="q<?php echo $index + 1; ?>d">D. <?php echo htmlspecialchars($question['answerD']); ?></label>
              </div>
            </div>
          </fieldset>
        <?php endforeach; ?>
      </div>

      <div class="actions">
        <a class="btn btn-ghost" href="LearnerHomepage.php">← Back</a>
        <div class="req">All questions are required</div>
        <button type="submit" class="btn btn-primary">Submit Quiz</button>
      </div>
    </form>
  </section>
</main>

<!-- ====== FOOTER ====== -->
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

<!-- ====== VALIDATION SCRIPT ====== -->
<script>
// Prevent submit if any question is unanswered
const form = document.getElementById('quizForm');

form.addEventListener('submit', function(e){
    // Remove previous highlights
    document.querySelectorAll('.q.missing').forEach(el => el.classList.remove('missing'));

    let firstMissing = null;
    let allAnswered = true;

    // Check each question group
    document.querySelectorAll('fieldset[data-group]').forEach((fieldset, index) => {
        const groupName = `q${index + 1}`;
        const radioName = fieldset.querySelector('input[type="radio"]').name;
        const checked = form.querySelector(`input[name="${radioName}"]:checked`);
        
        if (!checked) {
            fieldset.classList.add('missing');
            allAnswered = false;
            if (!firstMissing) firstMissing = fieldset;
        }
    });

    if (!allAnswered) {
        e.preventDefault();
        if (firstMissing) {
            firstMissing.scrollIntoView({behavior:'smooth', block:'center'});
        }
        alert('Please answer all questions before submitting.');
    }
});

// Remove red highlight when user selects an option
form.addEventListener('change', (e) => {
    const target = e.target;
    if (target.matches('input[type="radio"]')){
        const fs = target.closest('fieldset.q');
        if (fs) fs.classList.remove('missing');
    }
});
</script>
</body>
</html>