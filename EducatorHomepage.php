<?php
session_start();
require "db_connect.php";
require "config.php";

//We look for user in database
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
  // You can pass a message via a query param
  header("Location: login.php?error=Please+log+in+as+an+educator");
  exit;
}

//Cast ID as integer
$educatorId = (int)$_SESSION['user_id'];

//Get educator info
$uStmt = $pdo->prepare("SELECT firstName, lastName, emailAddress, photoFileName FROM User WHERE id = ?");
$uStmt->execute([$educatorId]);
$user = $uStmt->fetch();

if (!$user) {
  // Session says logged in, but user not found -> force logout
  header("Location: login.php?error=Account+not+found");
  exit;
}

// Precompute handy values for the HTML
$fullName = $user['firstName'] . ' ' . $user['lastName'];
$photoUrl = UPLOAD_URL_USERS . ($user['photoFileName'] ?: DEFAULT_USER_PHOTO);

// 2) Fetch quizzes + stats (6.c)
$qStmt = $pdo->prepare("
  SELECT
    q.id AS quiz_id,
    t.topicName AS topic_name,

    /* questions count */
    (SELECT COUNT(*) FROM QuizQuestion qq WHERE qq.quizID = q.id) AS question_count,

    /* taken count + avg score */
    (SELECT COUNT(*) FROM TakenQuiz tq WHERE tq.quizID = q.id) AS taken_count,
    (SELECT ROUND(AVG(tq.score), 2) FROM TakenQuiz tq WHERE tq.quizID = q.id) AS avg_score,

    /* feedback avg rating */
    (SELECT ROUND(AVG(f.rating), 2) FROM QuizFeedback f WHERE f.quizID = q.id) AS avg_rating

  FROM Quiz q
  JOIN Topic t ON t.id = q.topicID
  WHERE q.educatorID = ?
  ORDER BY t.topicName ASC, q.id ASC
");
$qStmt->execute([$educatorId]);
$quizzes = $qStmt->fetchAll();

$topicsStmt = $pdo->prepare("
  SELECT DISTINCT t.topicName
  FROM Quiz q
  JOIN Topic t ON t.id = q.topicID
  WHERE q.educatorID = ?
  ORDER BY t.topicName
");
$topicsStmt->execute([$educatorId]);
$topics = $topicsStmt->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Spark • Educator Home</title>

<!-- Add jQuery for AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
body{
  margin:0; font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:var(--text);
  background:linear-gradient(180deg, rgba(34,211,238,.06) 0%, rgba(34,211,238,0) 40%, rgba(245,158,11,.06) 100%), var(--bg);
}
a{color:inherit; text-decoration:none}
.container{max-width:1100px; margin:0 auto; padding:24px}

.wrap{
  max-width:900px; margin:90px auto; padding:0 20px;
}

/* cards */
.card{
  background:var(--panel);
  border:1px solid var(--border);
  border-radius:var(--radius);
  box-shadow:0 10px 30px rgba(0,0,0,.35),0 0 0 1px var(--border);
}

.section-title{margin:0 0 12px;font-size:1.25rem;color:#e5e7eb}

/* welcome */
.welcome{margin:0 0 8px;font-size:1.8rem;font-weight:500;color:#e5e7eb;letter-spacing:.2px;}
.welcome h1{margin:0;font-size:1.6rem;font-weight:700}
.muted{color:var(--muted);}
.empty{font-style:italic;color:var(--muted)}

/* educator info */
.info{padding:20px;display:grid;gap:16px;grid-template-columns:1fr 140px}
.info dl{display:grid;grid-template-columns:160px 1fr;gap:8px 16px;margin:0}
.info dt{color:var(--muted)}
.info dd{margin:0;color:#e5e7eb}
.info .avatar{
  width:140px;height:140px;border-radius:10px;background:#0f172a;
  border:1px solid var(--border);display:grid;place-items:center;color:#9aa3b2;font-weight:600
}

.educator-info h2{margin-top:0;color:rgba(229,231,235,.72);}
.educator-info p{margin:8px 0;color:rgba(229,231,235,.72);}

/* Spark tagline */
.motivation{
  margin-top: 90px;
  margin-bottom: 90px;
  font-size: 40px;
  font-weight: 500;
  text-align: center;
  font-style: normal;
  letter-spacing: 2px;
  background: linear-gradient(90deg, #fde047, #facc15, #f97316, #ef4444);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

/* tables (Your Quizzes + Recommendations) */
.table-wrap{
  overflow-x:auto;background:var(--panel);
  border:1px solid var(--border);border-radius:12px;margin-top:8px
}
table{
  width:100%;min-width:720px;border-collapse:collapse;color:#e5e7eb;background:transparent
}
thead th{
  text-align:left;font-weight:500;padding:12px 14px;
  background:rgba(255,255,255,.03);border-bottom:1px solid var(--border)
}
tbody td{
  padding:12px 14px;border-top:1px solid var(--border);vertical-align:middle;color:var(--muted)
}
tbody tr:hover{background:rgba(255,255,255,.02)}
.topic a{color:var(--accent);text-decoration:underline;font-weight:600}
.stat-chip{
  display:inline-block;padding:4px 10px;border-radius:999px;font-size:.92rem;font-weight:500;
  border:1px solid var(--border);color:#e5e7eb;background:rgba(255,255,255,.04);margin-right:6px
}

/* recommended question cells */
.learner{display:flex;gap:10px;align-items:center}
.learner .mini{
  width:40px;height:40px;border-radius:10px;background:#0f172a;border:1px solid var(--border);
  display:grid;place-items:center;font-size:.8rem;color:#9aa3b2
}
.q{display:grid;gap:10px}
.q .q-figure{
  width:100%;height:120px;background:#0f172a;border:1px solid var(--border);border-radius:10px;
  display:grid;place-items:center;color:#9aa3b2;font-size:.9rem
}
.q .q-text{font-weight:600;color:#e5e7eb}
.q ul{list-style:none;padding:0;margin:0;display:grid;gap:6px}
.q li{
  padding:8px 10px;border:1px solid var(--border);border-radius:10px;background:rgba(255,255,255,.02);
  color:var(--muted)
}
.q li.correct{
  outline:2px solid var(--ok);background:rgba(52,211,153,.10)
}

/* review form */
.review form{display:grid;gap:10px}
.review textarea{
  width:100%;min-height:100px;border-radius:10px;background:#0f172a;color:#e5e7eb;
  border:1px solid var(--border);padding:10px;resize:vertical;box-sizing:border-box
}
.review .choices{display:flex;gap:14px;align-items:center;justify-content:flex-start;margin:.2em 0}
.review .choices label{display:flex;gap:6px;align-items:center;color:var(--muted)}
.btn{
  border:none;border-radius:12px;padding:10px 14px;font-weight:800;cursor:pointer
}
.btn.primary{background:linear-gradient(90deg,var(--accent),var(--accentDeep));color:#111827}
.btn:active{transform:translateY(1px)}

/* Loading state */
.loading {
  opacity: 0.6;
  pointer-events: none;
}

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

/* responsive */
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
      <a href="EducatorHomepage.php">
        <img src="Media/sparklogo.png" alt="Spark Logo" class="header-logo">
      </a>
    </div>
    <div class="header-right">
      <a href="logout.php" class="signout-btn">Sign Out</a>
    </div>
  </div>
</header>

<div class="wrap">
    <!-- Welcome -->
    <div>
        <h1 class="welcome">Welcome, <span id="firstName" style="color:#facc15;"><?= htmlspecialchars($user['firstName']) ?></span></h1>
    </div>

    <!-- Educator Information -->
    <section class="card info" aria-labelledby="educator-info">
      <div class="educator-info">
        <h2>Educator Information</h2>
        <p><strong>First Name:</strong> <?= htmlspecialchars($user['firstName']) ?></p>
        <p><strong>Last Name:</strong> <?= htmlspecialchars($user['lastName']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['emailAddress']) ?></p>
        <p><strong>Topics:</strong>
          <?= $topics ? htmlspecialchars(implode(', ', $topics)) : '<span class="empty">no topics yet</span>' ?>
        </p>        
      </div>
      <div class="avatar" aria-label="Profile photo placeholder">
        <img src="<?php 
          if ($user['photoFileName'] && $user['photoFileName'] !== 'default_user.png' && file_exists('uploads/users/' . $user['photoFileName'])) {
              echo 'uploads/users/' . htmlspecialchars($user['photoFileName']);
          } else {
              echo 'Media/Defaultavatar.jpg';
          }
        ?>" alt="Profile Photo" class="avatar">
      </div>
    </section>

    <br>

    <div class="motivation-wrap">
      <p class="motivation">Light it up with Spark!</p>
      <span class="motivation-star" aria-hidden="true"></span>
    </div>

    <!-- Quizzes Table -->
    <h2 class="section-title">Your Quizzes</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Topic</th>
            <th>Number of Questions</th>
            <th>Quiz Statistics</th>
            <th>Quiz Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$quizzes): ?>
            <tr><td colspan="4" class="empty">You have no quizzes yet.</td></tr>
          <?php else: ?>
            <?php foreach ($quizzes as $q): 
              $topic = $q['topic_name'];
              $qid   = (int)$q['quiz_id'];
              $qc    = (int)$q['question_count'];
              $taken = (int)$q['taken_count'];
              $avgS  = $q['avg_score'];
              $avgR  = $q['avg_rating'];
            ?>
              <tr>
                <td class="topic" data-label="Topic">
                  <a href="quiz.php?id=<?= $qid ?>"><?= htmlspecialchars($topic) ?></a>
                </td>
                <td data-label="Number of Questions"><?= $qc ?></td>
                <td data-label="Quiz Statistics">
                  <?php if ($taken === 0): ?>
                    <span class="empty">quiz not taken yet</span>
                  <?php else: ?>
                    <span class="stat-chip">Takers: <?= $taken ?></span>
                    <span class="stat-chip">Average Score: <?= htmlspecialchars($avgS) ?>%</span>
                  <?php endif; ?>
                </td>
                <td data-label="Quiz Feedback">
                  <?php if ($avgR === null): ?>
                    <span class="empty">no feedback yet</span>
                  <?php else: ?>
                    <span class="stat-chip">Average Rating: <?= htmlspecialchars($avgR) ?>/5</span>
                    <a href="comments.php?quizID=<?= $qid ?>" class="muted">Comments</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php
    // Fetch pending recommended questions for this educator
    $recStmt = $pdo->prepare("
      SELECT
        rq.id AS rec_id,
        rq.quizID AS quiz_id,
        t.topicName AS topic_name,
        rq.learnerID AS learner_id,
        u.firstName  AS learner_first,
        u.lastName   AS learner_last,
        u.photoFileName AS learner_photo,
        rq.question       AS q_text,
        rq.questionFigureFileName AS q_figure_file,
        rq.answerA, rq.answerB, rq.answerC, rq.answerD,
        rq.correctAnswer  AS correct_letter
      FROM RecommendedQuestion rq
      JOIN Quiz  q ON q.id = rq.quizID
      JOIN Topic t ON t.id = q.topicID
      JOIN User  u ON u.id = rq.learnerID
      WHERE q.educatorID = ?
        AND rq.status = 'pending'
      ORDER BY rq.id ASC
    ");
    $recStmt->execute([$educatorId]);
    $recs = $recStmt->fetchAll();
    ?>

    <!-- Recommended Questions Table -->
    <h2 class="section-title">Question Recommendations</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Topic</th>
            <th>Learner</th>
            <th>Question</th>
            <th>Review</th>
          </tr>
        </thead>
        <tbody id="recsTable">
          <?php if (!$recs): ?>
            <tr><td colspan="4" class="empty">No pending recommendations.</td></tr>
          <?php else: ?>
            <?php foreach ($recs as $r):
              $recId     = (int)$r['rec_id'];
              $topicName = $r['topic_name'];
              $learner   = trim($r['learner_first'] . ' ' . $r['learner_last']);
              $learnerImg= UPLOAD_URL_USERS . ($r['learner_photo'] ?: DEFAULT_USER_PHOTO);
              $qText     = $r['q_text'];
              $figFile   = $r['q_figure_file'];
              $figUrl    = $figFile ? (UPLOAD_URL_RECOMMENDED . $figFile) : null;

              $answers = [
                'A' => $r['answerA'],
                'B' => $r['answerB'],
                'C' => $r['answerC'],
                'D' => $r['answerD'],
              ];
              $correct = $r['correct_letter'];
            ?>
              <tr data-rec-id="<?= $recId ?>">
                <td data-label="Topic"><?= htmlspecialchars($topicName) ?></td>
                <td data-label="Learner">
                  <div class="learner">
                    <div class="mini" aria-label="Learner photo">
                      <img class="mini" src="<?= htmlspecialchars($learnerImg) ?>" alt="user avatar">
                    </div>
                    <div><?= htmlspecialchars($learner) ?></div>
                  </div>
                </td>
                <td data-label="Question">
                  <div class="q">
                    <?php if ($figUrl): ?>
                      <div class="q-figure">
                        <img class="q-figure" src="<?= htmlspecialchars($figUrl) ?>" alt="question figure">
                      </div>
                    <?php endif; ?>
                    <div class="q-text"><?= htmlspecialchars($qText) ?></div>
                    <ul class="answers">
                      <?php foreach ($answers as $letter => $text): 
                        $isCorrect = ($letter === $correct);
                      ?>
                        <li<?= $isCorrect ? ' class="correct"' : '' ?>>
                          <?= htmlspecialchars($letter) ?>) <?= htmlspecialchars($text) ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </td>
                <td data-label="Review" class="review">
                  <!-- AJAX Review Form -->
                  <form class="review-form" method="post">
                    <input type="hidden" name="rec_id" value="<?= $recId ?>">
                    <textarea name="comment" placeholder="Write a short comment to the learner..."></textarea>
                    <div class="choices">
                      <label><input type="radio" name="approve" value="yes" required> Approve</label>
                      <label><input type="radio" name="approve" value="no" required> Disapprove</label>
                    </div>
                    <button type="submit" class="btn primary">Submit Review</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
</div>

<!-- ====== FOOTER ====== -->
<footer class="spark-footer">
  <div class="footer-top">
    <div class="footer-left">
      <a href="EducatorHomepage.php">
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
$(document).ready(function() {
    // AJAX handling for review forms
    $('#recsTable').on('submit', '.review-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var row = form.closest('tr');
        var submitBtn = form.find('button[type="submit"]');
        var originalText = submitBtn.text();
        
        // Validate form
        if (!form.find('input[name="approve"]:checked').length) {
            alert('Please select Approve or Disapprove before submitting.');
            return;
        }
        
        // Show loading state
        submitBtn.text('Processing...').prop('disabled', true);
        row.addClass('loading');
        
        // Send AJAX request
        $.ajax({
            type: 'POST',
            url: 'review_recommendation_ajax.php',
            data: form.serialize(),
            dataType: 'json',
            
   success: function(response) {
    if (response.success === true) {
        // Success - remove the row with animation
        row.fadeOut(300, function() {
            row.remove();
            
            // DEBUG: Check what we're counting
            console.log('Total rows:', $('#recsTable tr').length);
            console.log('Rows:', $('#recsTable tr'));
            
            // Check if table is empty now (count only data rows, excluding header)
            var dataRows = $('#recsTable tr').filter(function() {
                return $(this).find('td').length > 0; // Only rows with TD cells (data rows)
            });
            
            if (dataRows.length === 0) {
                $('#recsTable').html('<tr><td colspan="4" class="empty">No pending recommendations.</td></tr>');
            }
        });
    } else {
                    // Error
                    alert('Error: ' + (response.message || 'Failed to process recommendation'));
                    submitBtn.text(originalText).prop('disabled', false);
                    row.removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                alert('Error processing request. Please try again.');
                submitBtn.text(originalText).prop('disabled', false);
                row.removeClass('loading');
            }
        });
    });
});
</script>

</body>
</html>