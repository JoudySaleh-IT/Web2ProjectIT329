<?php
session_start(); // start the session so we can check login info

$isLoggedIn = isset($_SESSION['user_id']);
$userType   = $_SESSION['user_type'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Meta -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Spark</title>

  
  <!-- Styles -->
  <style>
   
    :root {
      --bg: #0b0f19;          /* near-black */
      --card: #111827;        /* gray-900 */
      --muted: #94a3b8;       /* slate-400 */
      --text: #e5e7eb;        /* gray-200 */
      --brand: #facc15;       /* yellow-400 */
      --brand-2: #f59e0b;     /* amber-500 */
      --ring: rgba(250,204,21,.35);
    }

    /* ======================================
       BASE
    ======================================= */
    * { box-sizing: border-box; }
   body{
  margin:0;
  font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  color: var(--text);

  /* base color */
  background-color: var(--bg);

  
  background-image:
    linear-gradient(180deg,
      rgba(34,211,238,.06) 0%,
      rgba(34,211,238,0) 45%,
      rgba(245,158,11,.06) 120%);
  background-repeat: no-repeat;
  background-attachment: fixed;   
  }
    a { color: inherit; text-decoration: none; }
    .container { max-width: 1100px; margin: 0 auto; padding: 24px; }

   
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
width: 150px; /* adjust as needed */
height: auto;
}
.header-title {
font-weight: 700;
font-size: 18px;
color: #facc15; /* Spark yellow highlight */
}
    .nav-actions { display: flex; gap: 12px; }

    /* Buttons (same size for Log in / Sign up everywhere) */
    .btn {
      display: inline-block;
      min-width: 14px;          /* make buttons equal width */
      padding: 12px 20px;
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 12px;
      background: #0b1222;
      color: var(--text);
      font-size: 15px;
      text-align: center;
      cursor: pointer;
    }
    .btn:hover { border-color: rgba(255,255,255,.2); }
    .btn-primary {
      background: linear-gradient(135deg, var(--brand), var(--brand-2));
      border: none;
      color: #0b1020;
      font-weight: 700;
    }

    
    .hero {
      display: grid;
      grid-template-columns: 1.2fr .8fr;
      gap: 28px;
      align-items: center;
      padding: 64px 0;
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 999px;
      background: rgba(17,24,39,.6);
      color: var(--muted);
      font-size: 12px;
    }
    .headline {
      font-size: 35px;
      line-height: 1.3;
      font-weight: 600;
      margin: 12px 0 12px;
      letter-spacing: -0.1px;
    }
    .sub { color: var(--muted); max-width: 55ch; }
    .cta { margin-top: 22px; display: flex; gap: 12px; flex-wrap: wrap; }

    /* Subject chips */
    .subject-row { display: flex; flex-wrap: wrap; gap: 10px; margin: 14px 0 0; }
    .chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.08);
      background: rgba(17,24,39,.65);
    }
    .chip svg { width: 16px; height: 16px; }

    /* Cards */
    .card {
      background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 6px 30px rgba(0,0,0,.35);
    }
    .features { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-top: 30px; }
    .feat h3 { margin: 10px 0 6px; font-size: 18px; }
    .feat p { margin: 0; color: var(--muted); font-size: 14px; }

    /* Decorative mock screen */
    .screen {
      aspect-ratio: 16/10;
      border-radius: 16px;
      overflow: hidden;
      position: relative;
      background: #0b1020;
      border: 1px solid rgba(255,255,255,.08);
    }
    .screen .bar {
      height: 34px;
      background: rgba(255,255,255,.05);
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 0 10px;
    }
    .dot { width: 10px; height: 10px; border-radius: 50%; }
    .dot.red { background: #ef4444; } .dot.yellow { background: #f59e0b; } .dot.green { background: #22c55e; }
    .tiles { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; padding: 14px; }
    .tile { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.06); border-radius: 12px; padding: 14px; }

   /* ===== FOOTER ===== */

html, body { height: 100%; }
body{
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: var(--bg);
  /* smooth, tall gradient */
  background-image: linear-gradient(
    180deg,
    rgba(34,211,238,.06) 0%,
    rgba(34,211,238,0) 45%,
    rgba(245,158,11,.06) 140%
  );
  background-repeat: no-repeat;
  background-attachment: fixed;
}
main.container{ flex: 1 0 auto; }

/* Footer styles */
.spark-footer {
background: linear-gradient(90deg, #0b0f19, #071123,#142645 );
color: #e5e7eb;
padding: 20px 16px 10px;
font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Arial;
border-top: 1px solid; /* thickness of the line */
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
width: 150px; /* adjust size */
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
background: #facc15; /* Spark yellow highlight */
color: #111827;
}
.footer-bottom {
text-align: center;
font-size: 14px;
margin-top: 14px;
color: #9ca3af;
}

  



    /* Responsive */
    @media (max-width: 900px) {
      .hero { grid-template-columns: 1fr; padding: 40px 0; }
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header class="spark-header">
<div class="header-inner">
<!-- Logo placeholder -->
<div class="header-left">
<a href="https://spark.infinityfree.me">
<img src="Media/sparklogo.png" alt="Spark Logo" class="header-logo">
</a>
</div>
   <div class="nav-buttons">
  <?php if (!$isLoggedIn): ?>
    <a href="login.php" class="btn">Login</a>
    <a href="signup.php" class="btn btn-primary">Sign Up</a>
  <?php else: ?>
    <?php if ($userType === 'educator'): ?>
      <a href="EducatorHomepage.php" class="btn">Educator Home</a>
    <?php else: ?>
      <a href="LearnerHomepage.php" class="btn">Learner Home</a>
    <?php endif; ?>
    <a href="signout.php" class="btn btn-danger">Sign Out</a>
  <?php endif; ?>
</div>
  </header>

  <!-- HERO -->
  <main class="container">
    <section class="hero">
      <!-- Left: copy + features -->
      <div>
        <span class="badge">Learn • Practice • Grow</span>

        <h1 class="headline">Stay sharp, Stay ready — with Spark</h1>
        <p class="sub">
  With Spark, studying becomes simpler and more engaging.<br>
  Take quick quizzes that help you review lessons, build steady practice habits, 
  and track your progress every step of the way.
</p>
        <!-- Subject icons -->
        <div class="subject-row" aria-label="Subjects">
          <span class="chip" title="Math">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 11h14v2H5v-2zm4-6h2v14H9V5zm6 0h2v14h-2V5z"/></svg>
            Math
          </span>
          <span class="chip" title="English">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h16v14H6l-2 2V4zm4 4h8v2H8V8zm0 4h8v2H8v-2z"/></svg>
            English
          </span>
          <span class="chip" title="Science">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 2h4v2l4 7v9a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-9l4-7V2zm-1.2 8L7 15v7h10v-7l-1.8-5H8.8z"/></svg>
            Science
          </span>
          <span class="chip" title="History">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5h12a4 4 0 0 1 4 4v10h-2V9a2 2 0 0 0-2-2H3V5zm0 4h10a4 4 0 0 1 4 4v6H3V9z"/></svg>
            History
          </span>
          <span class="chip" title="Health">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-7-4.35-7-10A5 5 0 0 1 12 6a5 5 0 0 1 7 5c0 5.65-7 10-7 10zm1-11h2v2h-2v2h-2v-2H9v-2h2V8h2v2z"/></svg>
            Health
          </span>
        </div>

        <!-- Calls to action -->
        <div class="cta">
          <a class="btn btn-primary" href="signup.php">Start studying</a>
          <a class="btn" href="login.php">I already have an account</a>
        </div>

        <!-- Key features -->
        <div class="features">
          <div class="feat card">
            <h3>Study your subjects</h3>
            <p>Practice quizzes for Math, English, Science, History, and Health.</p>
          </div>
          <div class="feat card">
            <h3>See your progress</h3>
            <p>Check scores and spot strengths and gaps before quizzes and exams.</p>
          </div>
          <div class="feat card">
            <h3>Suggest questions</h3>
            <p>Share your own questions; teachers review and add the best ones.</p>
          </div>
        </div>
      </div>

      <!-- Right: decorative mock UI (not functional) -->
      <div class="screen card" aria-hidden="true">
        <div class="bar">
          <span class="dot red"></span>
          <span class="dot yellow"></span>
          <span class="dot green"></span>
        </div>
        <div class="tiles">
          <div class="tile"></div><div class="tile"></div><div class="tile"></div>
          <div class="tile"></div><div class="tile"></div><div class="tile"></div>
        </div>
      </div>
    </section>
  </main>


<!-- ====== FOOTER ====== -->
<footer class="spark-footer">
<div class="footer-top">
<!-- Left: Logo -->
<div class="footer-left">
<a href="index.php">
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


</body>
</html>