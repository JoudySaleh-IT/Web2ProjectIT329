<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?> 

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Spark • Sign Up</title>
  <style>
    /* Your existing CSS styles remain exactly the same */
    html, body { height: 100%; }
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      color: var(--text);
      background-color: var(--bg);
      background-image: linear-gradient(180deg, rgba(34,211,238,.06) 0%, rgba(34,211,238,0) 45%, rgba(245,158,11,.06) 120%);
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
    main { flex: 1 0 auto; }
    :root { --bg:#0b0f19; --card:#111827; --muted:#94a3b8; --text:#e5e7eb; --brand:#facc15; --brand-2:#f59e0b; }
    * { box-sizing: border-box; }
    a { color: inherit; text-decoration: none; }
    
    .spark-header {
      background: none; color: #e5e7eb; padding: 10px 20px; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.25);
    }
    .header-inner { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
    .header-left { display: flex; align-items: center; }
    .header-logo { width: 150px; height: auto; }
    .header-title { font-weight: 700; font-size: 18px; color: #facc15; }
    .nav-actions { display: flex; gap: 12px; }
    .btn { padding: 10px 16px; border: 1px solid rgba(255,255,255,.08); border-radius: 10px; background: #0b1222; color: var(--text); text-align: center; }
    .btn:hover { border-color: rgba(255,255,255,.2); }
    .btn-primary { background: linear-gradient(135deg, var(--brand), var(--brand-2)); border: none; color: #0b1020; font-weight: 700; }
    .container { max-width: 1000px; margin: 0 auto; padding: 24px; }
    .grid { display: grid; grid-template-columns: 1fr; gap: 18px; justify-items: center; }
    .card { background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02)); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 24px; box-shadow: 0 6px 30px rgba(0,0,0,.35); max-width: 800px; width: 100%; margin-bottom: 10px; }
    .title { font-size: 28px; margin: 6px 0 8px; }
    .muted { color: var(--muted); }
    .type-picker { display: flex; gap: 10px; margin: 10px 0 6px; }
    .chip { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,.1); background: rgba(17,24,39,.7); cursor: pointer; }
    .chip input { accent-color: #f59e0b; }
    form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    label { font-size: 13px; color: var(--muted); }
    input[type="text"], input[type="email"], input[type="password"], select { padding: 12px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,.1); background: #0b1222; color: var(--text); }
    input::file-selector-button { padding: 8px 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,.1); background: #141c2f; color: var(--text); cursor: pointer; }
    .actions { grid-column: 1 / -1; display: flex; gap: 10px; align-items: center; margin-top: 6px; }
    .img-preview { width: 80px; height: 80px; border-radius: 12px; border: 1px solid rgba(255,255,255,.1); background: #0b1222; object-fit: cover; }
    .hidden { display: none; }
    .error-message { color: #ef4444; background: rgba(239,68,68,0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(239,68,68,0.3); }
    
    /* Footer styles */
    .spark-footer { background: linear-gradient(90deg, #0b0f19, #071123,#142645 ); color: #e5e7eb; padding: 20px 16px 10px; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial; border-top: 1px solid; border-image: linear-gradient(to right, #cc6d01, #facc15) 1; }
    .footer-top { max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
    .footer-left .footer-logo { width: 150px; height: auto; }
    .footer-right { display: flex; gap: 16px; }
    .footer-right .social { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 255, 255, 0.08); color: #e5e7eb; text-decoration: none; transition: background 0.2s, color 0.2s; }
    .footer-right .social:hover { background: #facc15; color: #111827; }
    .footer-bottom { text-align: center; font-size: 14px; margin-top: 14px; color: #9ca3af; }
    .intro-card { width: 480px; padding: 40px 32px; min-height: 250px; }
    
    @media (max-width: 900px) { form { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header class="spark-header">
    <div class="header-inner">
      <div class="header-left">
        <a href="index.php">
          <img src="Media/sparklogo.png" alt="Spark Logo" class="header-logo">
        </a>
      </div>
      <nav class="nav-actions">
        <a class="btn" href="login.php">Log in</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <section class="grid">
      <div class="card intro-card">
        <p class="muted">Create your account</p>
        <h1 class="title">Sign up to Spark</h1>

        <!-- Error Message Display -->
        <?php if (isset($_GET['error'])): ?>
          <div class="error-message">
            <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
          </div>
        <?php endif; ?>

        <!-- نوع المستخدم -->
        <div class="type-picker" role="radiogroup" aria-label="Choose user type">
          <label class="chip"><input type="radio" name="userType" value="learner" required> Learner</label>
          <label class="chip"><input type="radio" name="userType" value="educator"> Educator</label>
        </div>
        <p class="muted" style="font-size:12px; margin-top:0">Select your role.</p>

        <!-- Learner form -->
        <form id="learnerForm" class="hidden" method="post" action="process_signup.php" enctype="multipart/form-data">
          <input type="hidden" name="userType" value="learner">

          <div class="field">
            <label for="lfname">First name *</label>
            <input id="lfname" name="firstName" type="text" placeholder="e.g., Sara" required />
          </div>

          <div class="field">
            <label for="llname">Last name *</label>
            <input id="llname" name="lastName" type="text" placeholder="e.g., Al-Zahrani" required />
          </div>

          <div class="field">
            <label for="lemail">Email *</label>
            <input id="lemail" name="emailAddress" type="email" placeholder="name@example.com" required />
          </div>

          <div class="field">
            <label for="lpass">Password *</label>
            <input id="lpass" name="password" type="password" placeholder="••••••••" required />
          </div>

          <div class="field">
            <label for="limg">Profile image (optional)</label>
            <input id="limg" name="photo" type="file" accept="image/*" />
          </div>

          <div class="field" style="align-items:center">
            <label style="font-size:11px">Preview</label>
            <img id="lpreview" class="img-preview" src="Media/Defaultavatar.jpg" alt="Profile preview (default if none)" />
          </div>

          <div class="actions">
            <button class="btn btn-primary" type="submit">Create learner account</button>
            <a class="btn" href="index.php">Cancel</a>
          </div>
        </form>

        <!-- Educator form -->
        <form id="educatorForm" class="hidden" method="post" action="process_signup.php" enctype="multipart/form-data">
          <input type="hidden" name="userType" value="educator">

          <div class="field">
            <label for="efname">First name *</label>
            <input id="efname" name="firstName" type="text" placeholder="e.g., Ali" required />
          </div>

          <div class="field">
            <label for="elname">Last name *</label>
            <input id="elname" name="lastName" type="text" placeholder="e.g., Al-Harbi" required />
          </div>

          <div class="field">
            <label for="eemail">Email *</label>
            <input id="eemail" name="emailAddress" type="email" placeholder="name@example.com" required />
          </div>

          <div class="field">
            <label for="epass">Password *</label>
            <input id="epass" name="password" type="password" placeholder="••••••••" required />
          </div>

          <div class="field">
            <label for="eimg">Profile image (optional)</label>
            <input id="eimg" name="photo" type="file" accept="image/*" />
          </div>

          <div class="field" style="align-items:center">
            <label style="font-size:11px">Preview</label>
            <img id="epreview" class="img-preview" src="Media/Defaultavatar.jpg" alt="Profile preview (default if none)" />
          </div>

          <!-- Topics -->
          <div class="field" style="grid-column:1/-1">
            <label>Topics you teach *</label>
            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:6px">
              <label class="chip"><input type="checkbox" name="topics[]" value="Math"> Math</label>
              <label class="chip"><input type="checkbox" name="topics[]" value="English"> English</label>
              <label class="chip"><input type="checkbox" name="topics[]" value="History"> History</label>
            </div>
            <p class="muted" style="font-size:12px; margin:6px 0 0">(Choose one or more.)</p>
          </div>

          <div class="actions">
            <button class="btn btn-primary" type="submit">Create educator account</button>
            <a class="btn" href="index.php">Cancel</a>
          </div>
        </form>
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer class="spark-footer">
    <div class="footer-top">
      <div class="footer-left">
        <a href="index.php">
          <img src="Media/sparklogo.png" alt="Spark Logo" class="footer-logo">
        </a>
      </div>
      <div class="footer-right">
        <!-- Social icons -->
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
    const typeRadios   = document.querySelectorAll('input[name="userType"]');
    const learnerForm  = document.getElementById('learnerForm');
    const educatorForm = document.getElementById('educatorForm');

    function setRequired(formEl, on) {
      formEl.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]').forEach(i => {
        if (on) i.setAttribute('required', 'required');
        else i.removeAttribute('required');
      });
    }
    
    function setDisabled(formEl, on) {
      formEl.querySelectorAll('input, button, select').forEach(i => i.disabled = !!on);
    }

    function showForm(which) {
      if (which === 'learner') {
        learnerForm.classList.remove('hidden');
        educatorForm.classList.add('hidden');
        setRequired(learnerForm, true);
        setRequired(educatorForm, false);
        setDisabled(learnerForm, false);
        setDisabled(educatorForm, true);
      } else if (which === 'educator') {
        educatorForm.classList.remove('hidden');
        learnerForm.classList.add('hidden');
        setRequired(educatorForm, true);
        setRequired(learnerForm, false);
        setDisabled(educatorForm, false);
        setDisabled(learnerForm, true);
      }
      window.scrollTo({ top: document.body.scrollHeight / 5, behavior: 'smooth' });
    }

    typeRadios.forEach(r => r.addEventListener('change', () => {
      if (r.checked) showForm(r.value);
    }));

    // previews
    const defaultAvatar = 'Media/Defaultavatar.jpg';
    function wirePreview(fileInput, imgEl){
      if(!fileInput || !imgEl) return;
      fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];
        imgEl.src = file ? URL.createObjectURL(file) : defaultAvatar;
      });
    }
    wirePreview(document.getElementById('limg'), document.getElementById('lpreview'));
    wirePreview(document.getElementById('eimg'), document.getElementById('epreview'));

    // Educator form validation - require at least one topic
    const topicInputs = document.querySelectorAll('input[name="topics[]"]');

    function validateTopics() {
        const anyChecked = [...topicInputs].some(i => i.checked);
        // Show validation message
        if (topicInputs[0]) {
            topicInputs[0].setCustomValidity(anyChecked ? "" : "Please select at least one topic.");
        }
        return anyChecked;
    }

    // Validate when topics change
    topicInputs.forEach(i => i.addEventListener('change', validateTopics));

    // Validate before form submission
    educatorForm.addEventListener('submit', (e) => {
        const topicsOK = validateTopics();
        const fieldsOK = educatorForm.checkValidity();
        
        if (!topicsOK || !fieldsOK) {
            e.preventDefault(); // Stop form submission
            educatorForm.reportValidity(); // Show validation messages
        }
        // If validation passes, form will submit normally to PHP
    });
  </script>
</body>
</html>