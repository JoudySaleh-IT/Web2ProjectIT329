<?php
session_start();
require "db_connect.php";
require "config.php";
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
/* 7a) Allow learners only */
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'learner') {
  header('Location: login.php?error=Please+log+in+as+a+learner'); exit;
}

$learnerId = (int)$_SESSION['user_id'];

/* 7b) Load learner info */
$uStmt = $pdo->prepare("SELECT firstName, lastName, emailAddress, photoFileName FROM User WHERE id = ?");
$uStmt->execute([$learnerId]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
  header("Location: login.php?error=Account+not+found");
  exit;
}
$learnerPhoto = UPLOAD_URL_USERS . ($user['photoFileName'] ?: DEFAULT_USER_PHOTO);

/* 7c) Get topic list for the filter */
$tStmt = $pdo->query("SELECT id, topicName FROM Topic ORDER BY topicName ASC");
$topics = $tStmt->fetchAll(PDO::FETCH_ASSOC);

/* 7d / 7e) Get ALL quizzes initially - AJAX will handle filtering */
$qSql = "
  SELECT
    q.id AS quiz_id,
    t.topicName,
    e.firstName AS educator_first,
    e.lastName  AS educator_last,
    e.photoFileName AS educator_photo,
    (SELECT COUNT(*) FROM QuizQuestion qq WHERE qq.quizID = q.id) AS question_count
  FROM Quiz q
  JOIN Topic t   ON t.id = q.topicID
  JOIN User  e   ON e.id = q.educatorID
  ORDER BY t.topicName ASC, q.id ASC
";
$qStmt = $pdo->prepare($qSql);
$qStmt->execute();
$quizzes = $qStmt->fetchAll(PDO::FETCH_ASSOC);

/* 7f) This learner's recommended questions */
$rqSql = "
  SELECT
    rq.id,
    rq.quizID,
    rq.question,
    rq.questionFigureFileName,
    rq.answerA, rq.answerB, rq.answerC, rq.answerD,
    rq.correctAnswer,
    rq.status,
    rq.comments,
    t.topicName,
    e.firstName AS educator_first,
    e.lastName  AS educator_last,
    e.photoFileName AS educator_photo
  FROM RecommendedQuestion rq
  JOIN Quiz q   ON q.id = rq.quizID
  JOIN Topic t  ON t.id = q.topicID
  JOIN User  e  ON e.id = q.educatorID
  WHERE rq.learnerID = ?
  ORDER BY rq.id DESC
";
$rqStmt = $pdo->prepare($rqSql);
$rqStmt->execute([$learnerId]);
$recs = $rqStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Spark • Learner Home</title>

<!-- Add jQuery for AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
:root {
  --bg: #0b0f19;
  --panel: #111827;
  --border: #1f2937;
  --text: #e5e7eb;
  --muted: #9ca3af;
  --accent: #facc15;
  --accentDeep: #f59e0b;
  --ok: #34d399;
  --radius: 16px;
  --shadow: 0 10px 28px rgba(0,0,0,.28);
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  color: var(--text);
  background: linear-gradient(180deg, rgba(34,211,238,.06) 0%, rgba(34,211,238,0) 40%, rgba(245,158,11,.06) 100%), var(--bg);
}

a {
  color: inherit;
  text-decoration: none;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 24px;
}

/* HEADER */
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
  background: linear-gradient(90deg,#facc15,#f59e0b);
  color: #111827;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 700;
  text-decoration: none;
  transition: background .2s;
}

.main-content {
  max-width: 900px;
  margin: 90px auto;
  padding: 0 20px;
}

/* Welcome + card */
.welcome {
  margin: 0 0 8px;
  font-size: 1.8rem;
  font-weight: 500;
  color: #e5e7eb;
  letter-spacing: .2px;
}

.welcome .first-name {
  color: #facc15;
}

.learner-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #111827;
  border: 1px solid rgba(229,231,235,.12);
  border-radius: 12px;
  padding: 20px 30px;
  box-shadow: 0 10px 30px rgba(0,0,0,.35), 0 0 0 1px rgba(229,231,235,.12);
}

.learner-info h2 {
  margin-top: 0;
  color: rgba(229,231,235,.72);
}

.learner-info p {
  margin: 8px 0;
  color: rgba(229,231,235,.72);
}

.learner-photo img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border: 1px solid rgba(229,231,235,.12);
  border-radius: 10px;
}

/* Buttons */
.btn {
  background: linear-gradient(90deg,#facc15,#f59e0b);
  color: #111827;
  border: none;
  padding: 8px 14px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.btn-ghost {
  background: transparent;
  color: #e5e7eb;
  border: 1px solid rgba(229,231,235,.12);
  padding: 8px 14px;
  border-radius: 8px;
  font-weight: 500;
}

/* Quizzes section */
.quizzes-section {
  margin-top: 50px;
}

.quizzes-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.quizzes-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #e5e7eb;
  margin-right: auto; /* Push everything else to the right */
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: auto; /* Push filter to the right */
}

.filter-controls select {
  padding: 8px 12px;
  background: rgba(120, 100, 157, 0.04);
  color: #e5e7eb;
  border: 1px solid rgba(229,231,235,.12);
  border-radius: 8px;
  font-size: .95rem;
  min-width: 150px;
}

.filter-controls select:focus {
  outline: 2px solid rgba(250,204,21,.35);
  outline-offset: 1px;
}

.quizzes-controls {
  display: flex;
  gap: 8px;
}

.table-wrap {
  overflow-x: auto;
  background: #111827;
  border: 1px solid rgba(229,231,235,.12);
  border-radius: 12px;
}

.quiz-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 700px;
  color: #e5e7eb;
}

.quiz-table thead th {
  background: rgba(255,255,255,.03);
  text-align: left;
  padding: 12px 14px;
  font-weight: 500;
  border-bottom: 1px solid rgba(229,231,235,.12);
}

.quiz-table td {
  padding: 12px 14px;
  border-top: 1px solid rgba(229,231,235,.12);
  vertical-align: middle;
  color: rgba(229,231,235,.72);
}

tbody tr:hover {
  background: rgba(255,255,255,.02);
}

.educator {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.educator img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid rgba(229,231,235,.12);
}

.action a {
  font-weight: 500;
  text-decoration: underline;
  color: #facc15;
}

.action a:hover {
  opacity: .9;
}

/* Recommended Questions */
.recommended-section {
  margin-top: 32px;
}

.recommended-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 12px;
}

.recommended-header h2 {
  margin: 0;
  color: #e5e7eb;
}

.btn-link {
  font-weight: 500;
  text-decoration: underline;
  color: #facc15;
}

.btn-link:hover {
  opacity: .85;
}

.rq-item {
  padding: 6px 0;
}

.rq-text {
  font-weight: 500;
  margin-bottom: 6px;
  color: #e5e7eb;
}

.rq-options {
  list-style: none;
  margin: 0;
  padding: 0;
}

.rq-options li {
  padding: 4px 8px;
  border-radius: 9px;
  margin-bottom: 4px;
  border: 1px solid rgba(229,231,235,.12);
  display: flex;
  gap: 8px;
  align-items: center;
  background: rgba(255,255,255,.02);
  color: rgba(229,231,235,.72);
}

.rq-options li.correct {
  background: rgba(29, 244, 54, 0.179);
  border-color: rgb(156, 244, 119);
  color: #bfbebe;
  font-weight: 500;
}

.status {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: .9rem;
  font-weight: 500;
  border: 1px solid rgba(229,231,235,.12);
  color: #e5ebe7;
  background: rgba(89, 175, 117, 0.04);
}

.visually-hidden, .sr-only {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
  margin: -1px;
  padding: 0;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

/* Loading indicator */
.loading {
  text-align: center;
  padding: 20px;
  color: var(--muted);
}

.loading::after {
  content: "Loading...";
}

/* Footer */
.spark-footer {
  background: linear-gradient(90deg,#0b0f19,#071123,#142645);
  color: #e5e7eb;
  padding: 20px 16px;
  border-top: 1px solid;
  border-image: linear-gradient(to right,#cc6d01,#facc15) 1;
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
  background: rgba(255,255,255,.08);
  color: #e5e7eb;
  text-decoration: none;
  transition: background .2s, color .2s;
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

/* Tagline */
.motivation {
  margin-top: 90px;
  margin-bottom: 90px;
  font-size: 40px;
  font-weight: 500;
  text-align: center;
  letter-spacing: 2px;
  background: linear-gradient(90deg,#fde047,#facc15,#f97316,#ef4444);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .quizzes-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
  
  .quizzes-header h2 {
    margin-right: 0;
    text-align: center;
  }
  
  .filter-controls {
    margin-left: 0;
    justify-content: center;
  }
  
  .quizzes-controls {
    justify-content: center;
  }
  
  .main-content {
    margin: 60px auto;
    padding: 0 15px;
  }
  
  .learner-card {
    flex-direction: column;
    gap: 20px;
    text-align: center;
  }
  
  .recommended-header {
    flex-direction: column;
    gap: 12px;
  }
  
  .motivation {
    font-size: 32px;
    margin-top: 60px;
    margin-bottom: 60px;
  }
}

@media (max-width: 480px) {
  .header-logo, .footer-logo {
    width: 120px;
  }
  
  .welcome {
    font-size: 1.5rem;
  }
  
  .motivation {
    font-size: 24px;
  }
  
  .filter-controls select {
    min-width: 120px;
  }
}
</style>
</head>

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

<body>
  <main class="main-content">
    <!-- Welcome -->
    <h1 class="welcome">Welcome, <span class="first-name"><?= htmlspecialchars($user['firstName']) ?></span></h1>

    <!-- Learner Information card -->
    <section class="learner-card">
      <div class="learner-info">
        <h2>Learner Information</h2>
        <p><strong>First Name:</strong> <?= htmlspecialchars($user['firstName']) ?></p>
        <p><strong>Last Name:</strong> <?= htmlspecialchars($user['lastName']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['emailAddress']) ?></p>
      </div>
      <div class="learner-photo">
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

    <!-- All Available Quizzes -->
    <section id="quizzes" class="quizzes-section">
      <div class="quizzes-header">
        <h2>All Available Quizzes</h2>

        <!-- AJAX Filter controls - No form submission, just a select -->
        <div class="filter-controls">
          <label for="topic-filter" class="visually-hidden">Filter by topic</label>
          <select id="topic-filter" name="topic_id">
            <option value="">All Topics</option>
            <?php foreach ($topics as $t): ?>
              <option value="<?= (int)$t['id'] ?>">
                <?= htmlspecialchars($t['topicName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <!-- Removed Filter button as per requirements -->
        </div>

        <!-- optional see more/less buttons (client side only) -->
        <div class="quizzes-controls">
          <button id="see-more" class="btn">See more</button>
          <button id="see-less" class="btn btn-ghost" hidden>See less</button>
        </div>
      </div>

      <div class="table-wrap">
        <table class="quiz-table" aria-label="All Available Quizzes">
          <thead>
            <tr>
              <th id="col-topic">Topic</th>
              <th id="col-educator">Educator</th>
              <th id="col-questions">Number of Questions</th>
              <th id="col-action">Action</th>
            </tr>
          </thead>
          <tbody id="quiz-tbody">
          <?php if (!$quizzes): ?>
            <tr><td colspan="4" class="empty">No quizzes found.</td></tr>
          <?php else: ?>
            <?php foreach ($quizzes as $row):
              $qid   = (int)$row['quiz_id'];
              $topic = $row['topicName'];
              $ecFn  = $row['educator_first'];
              $ecLn  = $row['educator_last'];
              $ecPhoto = UPLOAD_URL_USERS . ($row['educator_photo'] ?: DEFAULT_USER_PHOTO);
              $qCount = (int)$row['question_count'];
            ?>
              <tr data-questions="<?= $qCount ?>">
                <td><?= htmlspecialchars($topic) ?></td>
                <td>
                  <div class="educator">
                    <img src="<?= htmlspecialchars($ecPhoto) ?>" alt="Educator photo">
                    <span><?= htmlspecialchars($ecFn . ' ' . $ecLn) ?></span>
                  </div>
                </td>
                <td class="qcount"><?= $qCount ?></td>
                <td class="action">
                  <?php if ($qCount > 0): ?>
                    <a href="take_quiz.php?id=<?= $qid ?>" class="take-link">Take Quiz</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <br><br><br>

    <!-- 7f) Recommended Questions by this learner -->
    <section class="recommended-section">
      <div class="recommended-header">
        <h2>Recommended Questions</h2>
        <div class="recommended-actions">
          <button class="btn" onclick="window.location.href='recommend.php'">Recommend a Question</button>
        </div>
      </div>

      <div class="table-wrap">
        <table class="quiz-table" aria-label="Recommended Questions">
          <thead>
            <tr>
              <th id="rq-topic">Topic</th>
              <th id="rq-educator">Educator</th>
              <th id="rq-question">Question</th>
              <th id="rq-status">Status</th>
              <th id="rq-comments">Comments</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recs): ?>
              <tr><td colspan="5" class="empty">You haven't recommended any questions yet.</td></tr>
            <?php else: ?>
              <?php foreach ($recs as $r):
                $educatorPhoto = UPLOAD_URL_USERS . ($r['educator_photo'] ?: DEFAULT_USER_PHOTO);
                $statusLabel = ucfirst($r['status']); // pending/approved/disapproved -> Pending/Approved/Disapproved
                $answers = [
                  'A' => $r['answerA'],
                  'B' => $r['answerB'],
                  'C' => $r['answerC'],
                  'D' => $r['answerD'],
                ];
              ?>
              <tr>
                <td><?= htmlspecialchars($r['topicName']) ?></td>
                <td>
                  <div class="educator">
                    <img src="<?= htmlspecialchars($educatorPhoto) ?>" alt="Educator photo">
                    <span><?= htmlspecialchars($r['educator_first'] . ' ' . $r['educator_last']) ?></span>
                  </div>
                </td>
                <td>
                  <div class="rq-item">
                    <?php if (!empty($r['questionFigureFileName'])): ?>
                      <div style="margin-bottom:6px">
                        <img src="<?= htmlspecialchars(UPLOAD_URL_RECOMMENDED . $r['questionFigureFileName']) ?>" alt="Question figure" style="max-width:240px;border-radius:8px;border:1px solid rgba(229,231,235,.12)">
                      </div>
                    <?php endif; ?>
                    <div class="rq-text"><?= htmlspecialchars($r['question']) ?></div>
                    <ul class="rq-options">
                      <?php foreach ($answers as $letter => $text):
                        $isCorrect = ($letter === $r['correctAnswer']);
                      ?>
                        <li<?= $isCorrect ? ' class="correct"' : '' ?>>
                          <?= htmlspecialchars($letter) ?>) <?= htmlspecialchars($text) ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </td>
                <td><span class="status"><?= htmlspecialchars($statusLabel) ?></span></td>
                <td><?= htmlspecialchars($r['comments'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
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

<script>
  // helper: hide "Take Quiz" when questions = 0
  function applyTakeLinkRule(tbodyEl) {
    const rows = Array.from(tbodyEl.querySelectorAll('tr'));
    rows.forEach(row => {
      const count = parseInt(row.getAttribute('data-questions') || '0', 10);
      const link = row.querySelector('.take-link');
      if (count === 0 && link) link.style.display = 'none';
      if (count > 0 && link) link.style.display = ''; // reset if needed
    });
  }

  // AJAX filter functionality using jQuery
  $(document).ready(function() {
    // When topic selection changes
    $('#topic-filter').change(function() {
      const topicId = $(this).val();
      
      // Show loading
      $('#quiz-tbody').html('<tr><td colspan="4" class="loading">Loading quizzes...</td></tr>');
      
      // Send AJAX request
      $.ajax({
        url: 'filter_quizzes.php',
        type: 'POST',
        data: { topic_id: topicId },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            updateQuizTable(response.quizzes);
          } else {
            $('#quiz-tbody').html('<tr><td colspan="4" class="empty">Error loading quizzes</td></tr>');
          }
        },
        error: function() {
          $('#quiz-tbody').html('<tr><td colspan="4" class="empty">Error loading quizzes</td></tr>');
        }
      });
    });
    
    function updateQuizTable(quizzes) {
      const tbody = $('#quiz-tbody');
      
      if (quizzes.length === 0) {
        tbody.html('<tr><td colspan="4" class="empty">No quizzes found for this topic.</td></tr>');
        return;
      }
      
      let html = '';
      quizzes.forEach(function(quiz) {
        const takeQuizLink = quiz.question_count > 0 
          ? `<a href="take_quiz.php?id=${quiz.quiz_id}" class="take-link">Take Quiz</a>`
          : '';
        
        html += `
          <tr data-questions="${quiz.question_count}">
            <td>${escapeHtml(quiz.topicName)}</td>
            <td>
              <div class="educator">
                <img src="${escapeHtml(quiz.educator_photo)}" alt="Educator photo">
                <span>${escapeHtml(quiz.educator_first + ' ' + quiz.educator_last)}</span>
              </div>
            </td>
            <td class="qcount">${quiz.question_count}</td>
            <td class="action">${takeQuizLink}</td>
          </tr>
        `;
      });
      
      tbody.html(html);
      applyTakeLinkRule(tbody[0]);
    }
    
    function escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
  });

  // initial apply on page load
  (function initOnce() {
    const quizTbody = document.getElementById('quiz-tbody');
    if (quizTbody) applyTakeLinkRule(quizTbody);

    // see more/less (your existing code)
    const moreRows = quizTbody ? quizTbody.querySelectorAll('tr.more') : [];
    const btnMore  = document.getElementById('see-more');
    const btnLess  = document.getElementById('see-less');
    function showMore() {
      moreRows.forEach(r => r.hidden = false);
      if (btnMore) btnMore.hidden = true;
      if (btnLess) btnLess.hidden = false;
    }
    function showLess() {
      moreRows.forEach(r => r.hidden = true);
      if (btnMore) btnMore.hidden = moreRows.length === 0;
      if (btnLess) btnLess.hidden = true;
    }
    if (btnMore || btnLess) showLess();
    btnMore && btnMore.addEventListener('click', showMore);
    btnLess && btnLess.addEventListener('click', showLess);
  })();
</script>

</body>
</html>