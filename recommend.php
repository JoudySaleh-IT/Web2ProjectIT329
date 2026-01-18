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

// Get learner info for display
$uStmt = $pdo->prepare("SELECT firstName, lastName FROM User WHERE id = ?");
$uStmt->execute([$learnerId]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header("Location: login.php?error=Account+not+found");
    exit;
}

// Get all topics for dropdown (Requirement 14a)
$tStmt = $pdo->query("SELECT id, topicName FROM Topic ORDER BY topicName ASC");
$topics = $tStmt->fetchAll(PDO::FETCH_ASSOC);

// Check for errors from submission
$errors = [];
$formData = [];
if (isset($_SESSION['recommend_errors'])) {
    $errors = $_SESSION['recommend_errors'];
    unset($_SESSION['recommend_errors']);
}
if (isset($_SESSION['form_data'])) {
    $formData = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Spark • Recommend Question</title>
<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
  :root{
    --bg:#0b0f19; --card:#0f172a; --ring:#334155; --text:#e5e7eb;
    --muted:#9aa3b2; --warn:#fde047;
  }
  *{box-sizing:border-box}
  body{
    margin:0;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial;
    color:var(--text);
    background:
      linear-gradient(180deg, rgba(34,211,238,.06) 0%,
                             rgba(34,211,238,0) 40%,
                             rgba(245,158,11,.06) 100%),
      var(--bg);
  }

  /* ====== HEADER STYLES ====== */
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
  .header-left{ display:flex; align-items:center; }
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

  /* Page wrap */
  .wrap{ max-width:900px; margin:90px auto 60px; padding:0 16px; }

  h1{
    background:linear-gradient(90deg,#fde047,#facc15,#f97316,#ef4444);
    -webkit-background-clip:text; background-clip:text;
    color:transparent; -webkit-text-fill-color:transparent;
    padding-top:10px; padding-bottom:10px; text-align:center;
  }

  /* Card */
  .card{ background:var(--card); border:1px solid var(--ring);
        border-radius:14px; box-shadow:0 10px 28px rgba(0,0,0,.25); }
  .card-head{ padding:16px 18px; border-bottom:1px solid var(--ring); font-weight:700; }
  .card-body{ padding:18px; }

  /* Form grid alignment */
  .form-grid{
    display:grid; grid-template-columns:220px 1fr;
    gap:12px 16px;
  }
  .label{ align-self:center; color:var(--muted); font-size:14px; }
  .label.top{ align-self:start; padding-top:6px; }
  .control{
    width:100%; padding:10px 12px; background:#0b1224; color:var(--text);
    border:1px solid var(--ring); border-radius:10px;
  }
  textarea.control{ min-height:110px; resize:vertical; line-height:1.4; }
  input[type="file"].control{ padding:8px; }

  /* Loading state */
  .control.loading {
    background: #0b1224 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23facc15"><path d="M12 2a1 1 0 0 0-1 1v3a1 1 0 0 0 2 0V3a1 1 0 0 0-1-1zm0 15a1 1 0 0 0-1 1v3a1 1 0 0 0 2 0v-3a1 1 0 0 0-1-1zm8.66-10a1 1 0 0 0-.366 1.366l1.5 2.598a1 1 0 0 0 1.732-1l-1.5-2.598A1 1 0 0 0 20.66 7zM4.634 15.366a1 1 0 0 0-1.366.366l-1.5 2.598a1 1 0 0 0 1.732 1l1.5-2.598a1 1 0 0 0-.366-1.366zM22 11h-3a1 1 0 0 0 0 2h3a1 1 0 0 0 0-2zM5 11H2a1 1 0 0 0 0 2h3a1 1 0 0 0 0-2zm13.434 3.9a1 1 0 0 0-1.366.366l-1.5 2.598a1 1 0 0 0 1.732 1l1.5-2.598a1 1 0 0 0-.366-1.366zM6.566 5.634a1 1 0 0 0-.366-1.366l-2.598-1.5a1 1 0 0 0-1 1.732l2.598 1.5a1 1 0 0 0 1.366-.366z"/></svg>') no-repeat right 12px center;
    background-size: 16px 16px;
  }

  /* Actions */
  .actions{
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 18px; border-top:1px solid var(--ring);
    background:#0c1326; border-radius:0 0 14px 14px;
  }
  .btn{
    border:none; border-radius:12px; padding:10px 14px;
    font-weight:800; cursor:pointer; text-decoration:none;
  }
  .btn-primary{
    background:linear-gradient(90deg,#facc15,#f59e0b);
    color:#111827; border:0;
  }
  .btn-ghost{
    background:transparent; color:var(--muted);
    border:1px solid rgba(229,231,235,.12);
  }
  .btn:active{ transform:translateY(1px); }

  /* Error messages */
  .error-message {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 16px;
  }

  /* Success message */
  .success-message {
    color: #34d399;
    background: rgba(52, 211, 153, 0.1);
    border: 1px solid rgba(52, 211, 153, 0.3);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 16px;
  }

  /* Missing field highlight */
  .control.missing, select.control.missing, textarea.control.missing{
    outline:2px solid #ef4444;
    border-color:#ef4444;
    box-shadow:0 0 0 2px rgba(239,68,68,.25);
  }

  /* Mobile */
  @media (max-width:640px){
    .form-grid{ grid-template-columns:1fr; }
    .label{ padding-top:0; }
    .label.top{ padding-top:0; }
  }

  /* Footer */
  .spark-footer{
    background:linear-gradient(90deg,#0b0f19,#071123,#142645);
    color:#e5e7eb; padding:20px 16px 10px;
    font-family:system-ui,-apple-system, Segoe UI, Roboto, Ubuntu, Arial;
    border-top:1px solid;
    border-image:linear-gradient(to right,#cc6d01,#facc15) 1;
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
    background:rgba(255, 255, 255, 0.08); color:#e5e7eb;
    text-decoration:none; transition:background .2s, color .2s;
  }
  .footer-right .social:hover{ background:#facc15; color:#111827; }
  .footer-bottom{ text-align:center; font-size:14px; margin-top:14px; color:#9ca3af; }
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
  <h1>Recommend a Question</h1>

  <!-- Display error messages if any -->
  <?php if (!empty($errors)): ?>
    <div class="error-message">
      <strong>Please fix the following errors:</strong>
      <ul>
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <section class="card">
    <div class="card-head">Fill the form below</div>

    <div class="card-body">
      <!-- Form with validation - SUBMITS TO SEPARATE PHP PAGE -->
      <form id="recommendForm" action="submit_recommendation.php" method="post" enctype="multipart/form-data" novalidate>
        <div class="form-grid">
          <!-- Topic (Requirement 14a) -->
          <label class="label" for="topic_id">Select Topic *</label>
          <select id="topic_id" name="topic_id" class="control" required>
            <option value="" disabled selected>Select a topic</option>
            <?php foreach ($topics as $topic): ?>
              <option value="<?= (int)$topic['id'] ?>" 
                <?= (isset($formData['topic_id']) && $formData['topic_id'] == $topic['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($topic['topicName']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <!-- Educator (Requirement 14a - Phase 2: show all educators) -->
          <label class="label" for="educator_id">Select Educator *</label>
          <select id="educator_id" name="educator_id" class="control" required>
            <option value="" disabled selected>Select a topic first</option>
            <!-- Educators will be populated via AJAX based on topic selection -->
          </select>

          <!-- Figure (optional) -->
          <label class="label" for="figure">Optional Figure</label>
          <input id="figure" name="figure" type="file" class="control" accept="image/*" />

          <!-- Question text -->
          <label class="label top" for="question">Question Text *</label>
          <textarea id="question" name="question" class="control" placeholder="Type the question here..." required><?= isset($formData['question']) ? htmlspecialchars($formData['question']) : '' ?></textarea>

          <!-- Choice A -->
          <label class="label" for="answerA">Choice A *</label>
          <input id="answerA" name="answerA" type="text" class="control" placeholder="Enter choice A" 
                 value="<?= isset($formData['answerA']) ? htmlspecialchars($formData['answerA']) : '' ?>" required />

          <!-- Choice B -->
          <label class="label" for="answerB">Choice B *</label>
          <input id="answerB" name="answerB" type="text" class="control" placeholder="Enter choice B" 
                 value="<?= isset($formData['answerB']) ? htmlspecialchars($formData['answerB']) : '' ?>" required />

          <!-- Choice C -->
          <label class="label" for="answerC">Choice C *</label>
          <input id="answerC" name="answerC" type="text" class="control" placeholder="Enter choice C" 
                 value="<?= isset($formData['answerC']) ? htmlspecialchars($formData['answerC']) : '' ?>" required />

          <!-- Choice D -->
          <label class="label" for="answerD">Choice D *</label>
          <input id="answerD" name="answerD" type="text" class="control" placeholder="Enter choice D" 
                 value="<?= isset($formData['answerD']) ? htmlspecialchars($formData['answerD']) : '' ?>" required />

          <!-- Correct choice -->
          <label class="label" for="correctAnswer">Correct Choice *</label>
          <select id="correctAnswer" name="correctAnswer" class="control" required>
            <option value="" disabled selected>Select the correct choice</option>
            <option value="A" <?= (isset($formData['correctAnswer']) && $formData['correctAnswer'] === 'A') ? 'selected' : '' ?>>A</option>
            <option value="B" <?= (isset($formData['correctAnswer']) && $formData['correctAnswer'] === 'B') ? 'selected' : '' ?>>B</option>
            <option value="C" <?= (isset($formData['correctAnswer']) && $formData['correctAnswer'] === 'C') ? 'selected' : '' ?>>C</option>
            <option value="D" <?= (isset($formData['correctAnswer']) && $formData['correctAnswer'] === 'D') ? 'selected' : '' ?>>D</option>
          </select>

        </div>

        <!-- Actions -->
        <div class="actions">
          <a class="btn btn-ghost" href="LearnerHomepage.php">← Back to Homepage</a>
          <button type="submit" class="btn btn-primary">Submit Recommendation</button>
        </div>
      </form>
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

<!-- ====== AJAX AND VALIDATION SCRIPT ====== -->
<script>
  (function(){
    const form = document.getElementById('recommendForm');
    const topicSelect = document.getElementById('topic_id');
    const educatorSelect = document.getElementById('educator_id');
    const requiredFields = [
      'topic_id','educator_id','question','answerA','answerB','answerC','answerD','correctAnswer'
    ];
    const controls = requiredFields.map(id => document.getElementById(id));

    // AJAX function to load educators based on selected topic
    function loadEducatorsByTopic(topicId) {
      if (!topicId) {
        // Clear educator dropdown if no topic selected
        educatorSelect.innerHTML = '<option value="" disabled selected>Select a topic first</option>';
        return;
      }

      // Show loading state
      educatorSelect.classList.add('loading');
      educatorSelect.disabled = true;
      educatorSelect.innerHTML = '<option value="" disabled selected>Loading educators...</option>';

      // Make AJAX request using jQuery
      $.ajax({
        url: 'get_educators_by_topic.php',
        type: 'GET',
        data: { topic_id: topicId },
        dataType: 'json',
        success: function(data) {
          // Remove loading state
          educatorSelect.classList.remove('loading');
          educatorSelect.disabled = false;
          
          // Update educator dropdown
          educatorSelect.innerHTML = '<option value="" disabled selected>Select an educator</option>';
          
          if (data.length > 0) {
            data.forEach(function(educator) {
              const option = document.createElement('option');
              option.value = educator.id;
              option.textContent = educator.firstName + ' ' + educator.lastName;
              educatorSelect.appendChild(option);
            });
          } else {
            educatorSelect.innerHTML = '<option value="" disabled selected>No educators found for this topic</option>';
          }
        },
        error: function(xhr, status, error) {
          // Remove loading state
          educatorSelect.classList.remove('loading');
          educatorSelect.disabled = false;
          educatorSelect.innerHTML = '<option value="" disabled selected>Error loading educators</option>';
          console.error('AJAX Error:', error);
        }
      });
    }

    // Event listener for topic selection change
    topicSelect.addEventListener('change', function() {
      const selectedTopicId = this.value;
      loadEducatorsByTopic(selectedTopicId);
    });

    // Load educators if a topic is already selected (on page load)
    const initialTopicId = topicSelect.value;
    if (initialTopicId) {
      loadEducatorsByTopic(initialTopicId);
    }

    // Clear missing style on input
    form.addEventListener('input', (e)=>{
      if(e.target.classList.contains('missing')){
        e.target.classList.remove('missing');
      }
    });

    form.addEventListener('submit', function(e){
      let firstMissing = null;

      // validate required fields (figure is optional)
      controls.forEach(el=>{
        const isEmpty = (
          (el.tagName === 'SELECT' && (!el.value || el.value === '')) ||
          (el.tagName === 'TEXTAREA' && el.value.trim() === '') ||
          (el.tagName === 'INPUT' && el.type === 'text' && el.value.trim() === '')
        );

        if(isEmpty){
          el.classList.add('missing');
          if(!firstMissing) firstMissing = el;
        }else{
          el.classList.remove('missing');
        }
      });

      if(firstMissing){
        e.preventDefault();
        firstMissing.scrollIntoView({behavior:'smooth', block:'center'});
        alert('Please complete all required fields.');
        return; // stop submit
      }
    });
  })();
</script>
</body>
</html>