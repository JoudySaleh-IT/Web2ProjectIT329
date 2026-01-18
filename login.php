<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Spark • Log in</title>

  <style>
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
    .nav-actions { display: flex; gap: 12px; }

    .btn {
      padding: 10px 16px;
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 10px;
      background: #0b1222;
      color: var(--text);
      text-decoration: none;
      text-align: center;
      cursor: pointer;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
      border: none;
      color: #0b1020;
      font-weight: 700;
    }
    
    :root {
      --bg: #0b0f19;
      --card: #111827;
      --muted: #94a3b8;
      --text: #e5e7eb;
      --brand: #facc15;
      --brand-2: #f59e0b;
      --ring: rgba(255,255,255,.10);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      color: var(--text);
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
      display: flex;
      align-items: center;
      padding: 16px 24px;
    }

    .logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }

    .wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--ring);
      border-radius: 16px;
      padding: 28px;
      max-width: 360px;
      width: 100%;
      text-align: center;
    }

    h1 { font-size: 24px; margin-bottom: 6px; font-weight: 700; }
    .muted { color: var(--muted); font-size: 14px; margin-bottom: 18px; }

    .btn {
      display: block;
      width: 100%;
      padding: 10px 14px;
      border-radius: 10px;
      border: 1px solid var(--ring);
      background: #0b1222;
      color: var(--text);
      margin-bottom: 10px;
      cursor: pointer;
      text-decoration: none;
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
      border: none;
      color: #0b1020;
      font-weight: 700;
    }

    .link {
      background: none;
      border: 0;
      color: var(--muted);
      text-decoration: underline;
      cursor: pointer;
      font-size: 13px;
      margin-top: 6px;
    }

    .field { margin-bottom: 14px; text-align: left; }
    .field label { font-size: 13px; color: var(--muted); display: block; margin-bottom: 6px; }
    .field input {
      width: 100%; padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid var(--ring);
      background: #0b1222;
      color: var(--text);
    }

    .error-message {
        color: #ef4444;
        background: rgba(239,68,68,0.1);
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid rgba(239,68,68,0.3);
        font-size: 14px;
    }

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
      </nav>
    </div>
  </header>

  <main class="wrap">
    <section class="card">
      <h1>Welcome back</h1>
      <p class="muted">Enter your credentials to continue</p>

      <!-- Error Message Display -->
      <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
          <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="post" action="process_login.php">
        <div class="field">
          <label for="emailAddress">Email</label>
          <input id="emailAddress" name="emailAddress" type="email" required placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['emailAddress'] ?? ''); ?>">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required placeholder="••••••••">
        </div>
        <button class="btn btn-primary" type="submit">Log in</button>
        <a class="btn" href="signup.php">Create an account</a>
      </form>
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