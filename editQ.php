<?php
session_start();
require "db_connect.php";

// Security check - ensure user is logged in as educator
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'educator') {
    header("Location: login.php?error=Please+log+in+as+an+educator");
    exit;
}

// Get question ID from request
$questionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($questionId === 0) {
    die("Invalid question ID");
}

// Get question details and verify it belongs to the current educator
$questionStmt = $pdo->prepare("
    SELECT qq.*, q.id as quizID, q.educatorID, t.topicName 
    FROM QuizQuestion qq 
    JOIN Quiz q ON qq.quizID = q.id 
    JOIN Topic t ON q.topicID = t.id 
    WHERE qq.id = ? AND q.educatorID = ?
");
$questionStmt->execute([$questionId, $_SESSION['user_id']]);
$question = $questionStmt->fetch();

if (!$question) {
    die("Question not found or you don't have permission to edit this question");
}

$quizId = $question['quizID'];
$topicName = $question['topicName'];
$currentImage = $question['questionFigureFileName'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Spark • Edit Question</title>

  <style>
   :root{
    --bg:#0b0f19; --card:#0f172a; --ring:#334155; --text:#e5e7eb;
    --muted:#9aa3b2; --warn:#fde047;
  }
      .main-content{max-width:900px;margin:90px auto;padding:0 20px;}

    /* Reset & base */
    *{box-sizing:border-box}
    body{
      margin:0; font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:var(--text);
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
 
.signout-btn {
background:linear-gradient(90deg,#facc15,#f59e0b);color:#111827;border:none;padding:8px 14px;border-radius:10px;font-weight:700;cursor:pointer;
color: #111827;
padding: 8px 16px;
border-radius: 8px;
font-weight: 700;
text-decoration: none;
transition: background 0.2s;
}
 
h1{
    background:linear-gradient(90deg,#fde047,#facc15,#f97316,#ef4444);
    -webkit-background-clip:text; background-clip:text;
    color:transparent; -webkit-text-fill-color:transparent;
    padding-top:10px; padding-bottom:10px; text-align:center;
  }

    /* Card shell */
    .card{background:var(--card);border:1px solid var(--ring);border-radius:14px;box-shadow:0 10px 28px rgba(0,0,0,.25);}
    .card-head{padding:16px 18px;border-bottom:1px solid var(--ring);font-weight:700;}
    .card-body{padding:18px;margin: 12px;}

    /* Grid layout */
    .form-grid{
      display:grid;
      grid-template-columns: 220px 1fr;
      gap:12px 16px;
    }
    .label{align-self:center;color:var(--muted);font-size:14px;}
    .label.top{align-self:start;padding-top:6px;}
    .control{
      width:100%;padding:10px 12px;background:#0b1224;color:var(--text);
      border:1px solid var(--ring);border-radius:10px;font:inherit;
    }
    textarea.control{min-height:110px;resize:vertical;line-height:1.4;}
    input[type="file"].control{padding:8px;}

    /* Preview box */
    .preview{
      width:245px;height:160px;object-fit:contain;background:#111827;border:1px solid rgba(229,231,235,.25);
      border-radius:6px;padding:4px;display:block;
    }
    .preview-placeholder{
      width:245px;height:160px;display:flex;align-items:center;justify-content:center;
      background:#111827;border:1px solid rgba(229,231,235,.25);border-radius:6px;
      color:var(--muted);font-size:14px;
    }

    /* Actions container */
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
    .btn-danger{
      background:linear-gradient(90deg,#ef4444,#dc2626);
      color:#ffffff; border:0; font-size:12px; padding:6px 10px;
    }

    .error-message {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid #ef4444;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 16px;
    }

    .home-btn {
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--ring);
      padding: 8px 12px;
      border-radius: 8px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .current-image-info {
      color: var(--success);
      font-size: 12px;
      margin-top: 4px;
    }

    @media (max-width:640px){
      .form-grid{grid-template-columns:1fr;}
      .label{padding-top:0;}
      .label.top{padding-top:0;}
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
 
<body>
  <main class="main-content">

        <div class="topbar">
          <div style="display: flex; align-items: center; gap: 12px; justify-content: center;">
            <h1>Edit Question</h1>
          </div>
        </div>

    <section class="card">
      <div class="card-head">Edit the question details</div>
      <div class="card-body">
        <!-- Error messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?= htmlspecialchars(urldecode($_GET['error'])) ?></div>
        <?php endif; ?>

        <form id="editQuestionForm" method="POST" action="process_edit_question.php" enctype="multipart/form-data">
          <input type="hidden" name="question_id" value="<?= $questionId ?>">
          
          <div class="form-grid">
            <label class="label" for="topic">Topic</label>
            <input id="topic" name="topic" type="text" class="control" value="<?= htmlspecialchars($topicName) ?>" readonly />

            <label class="label top" for="question">Question</label>
            <textarea id="question" name="question" class="control" rows="5" required placeholder="Enter your question here..."><?= htmlspecialchars($question['question']) ?></textarea>

            <label class="label" for="figure">Optional Figure</label>
            <div>
              <input type="file" id="figure" name="figure" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="control" onchange="previewImage(this)" />
              <?php if ($currentImage): ?>
                <div class="current-image-info">Current image: <?= htmlspecialchars($currentImage) ?></div>
              <?php endif; ?>
            </div>

            <span class="label">Image Preview</span>
            <div id="imagePreviewContainer">
              <?php if ($currentImage): ?>
                <img id="imagePreview" class="preview" src="uploads/questions/<?= htmlspecialchars($currentImage) ?>" alt="Current question figure" />
                <div id="imagePreviewPlaceholder" class="preview-placeholder" style="display:none;">No image selected</div>
              <?php else: ?>
                <div id="imagePreviewPlaceholder" class="preview-placeholder">No image selected</div>
                <img id="imagePreview" class="preview" style="display:none;" alt="Image preview" />
              <?php endif; ?>
              <input type="hidden" name="remove_image" id="removeImageInput" value="0">
              <button type="button" id="removeImageBtn" class="btn btn-danger" style="margin-top: 10px; <?= $currentImage ? '' : 'display: none;' ?>" onclick="removeSelectedImage()">
                Remove image
              </button>
              <div class="instruction-text" id="removeInstruction" style="display: none;">
                Image will be removed when you save the question
              </div>
            </div>

            <label class="label" for="ansA">Answer A</label>
            <input id="ansA" name="ansA" type="text" class="control" required placeholder="Enter answer A" value="<?= htmlspecialchars($question['answerA']) ?>" />

            <label class="label" for="ansB">Answer B</label>
            <input id="ansB" name="ansB" type="text" class="control" required placeholder="Enter answer B" value="<?= htmlspecialchars($question['answerB']) ?>" />

            <label class="label" for="ansC">Answer C</label>
            <input id="ansC" name="ansC" type="text" class="control" required placeholder="Enter answer C" value="<?= htmlspecialchars($question['answerC']) ?>" />

            <label class="label" for="ansD">Answer D</label>
            <input id="ansD" name="ansD" type="text" class="control" required placeholder="Enter answer D" value="<?= htmlspecialchars($question['answerD']) ?>" />

            <label class="label" for="correct">Correct Answer</label>
            <select id="correct" name="correct" class="control" required>
              <option value="" disabled>Select correct answer</option>
              <option value="A" <?= $question['correctAnswer'] === 'A' ? 'selected' : '' ?>>A</option>
              <option value="B" <?= $question['correctAnswer'] === 'B' ? 'selected' : '' ?>>B</option>
              <option value="C" <?= $question['correctAnswer'] === 'C' ? 'selected' : '' ?>>C</option>
              <option value="D" <?= $question['correctAnswer'] === 'D' ? 'selected' : '' ?>>D</option>
            </select>
          </div>

          <div class="actions">
            <a class="btn btn-ghost" href="quiz.php?id=<?= $quizId ?>">← Back to Quiz</a>
            <button type="submit" class="btn btn-primary">Update Question</button>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script>
  function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('imagePreviewPlaceholder');
    const removeBtn = document.getElementById('removeImageBtn');
    const removeInput = document.getElementById('removeImageInput');
    const removeInstruction = document.getElementById('removeInstruction');
    
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
        removeBtn.style.display = 'block';
        removeInstruction.style.display = 'block';
        removeInput.value = '0'; // Reset remove flag when new image is selected
      }
      
      reader.readAsDataURL(input.files[0]);
    } else {
      if (preview && !preview.src.includes('uploads/questions/')) {
        preview.style.display = 'none';
      }
      if (placeholder) placeholder.style.display = 'flex';
      removeBtn.style.display = 'block'; // Keep remove button visible if there was an existing image
      removeInstruction.style.display = 'none';
    }
  }

  function removeSelectedImage() {
    const fileInput = document.getElementById('figure');
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('imagePreviewPlaceholder');
    const removeBtn = document.getElementById('removeImageBtn');
    const removeInput = document.getElementById('removeImageInput');
    const removeInstruction = document.getElementById('removeInstruction');
    
    // Clear the file input
    fileInput.value = '';
    
    // Hide preview and show placeholder
    if (preview) preview.style.display = 'none';
    if (placeholder) placeholder.style.display = 'flex';
    removeBtn.style.display = 'none';
    removeInstruction.style.display = 'block';
    
    // Set flag to indicate image should be removed
    removeInput.value = '1';
  }

  document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('figure');
    // Show remove instruction if there's a current image
    <?php if ($currentImage): ?>
      document.getElementById('removeImageBtn').style.display = 'block';
    <?php endif; ?>
  });
  </script>

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
</body>
</html>