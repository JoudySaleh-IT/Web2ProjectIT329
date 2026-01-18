<?php
// ---- Enhanced error reporting ----
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require __DIR__ . '/db_connect.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['emailAddress'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($email) || empty($password)) {
    header("Location: login.php?error=Please+fill+in+all+fields");
    exit;
}

// Check if user exists in database based on email
$stmt = $pdo->prepare("SELECT id, firstName, lastName, password, userType, photoFileName FROM User WHERE emailAddress = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {// لو فعلا فيه مستخدم بهذا الايميل والباسورد يتطابق احفظ البيانات 
    // Login successful - set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['userType'];
    $_SESSION['user_name'] = $user['firstName'] . ' ' . $user['lastName'];
    $_SESSION['user_photo'] = $user['photoFileName'];
    
    // Redirect based on user type
    if ($user['userType'] === 'educator') {
        header("Location: EducatorHomepage.php");
    } else {
        header("Location: LearnerHomepage.php");
    }
    exit;
} else {
    // Login failed
    header("Location: login.php?error=Invalid+email+or+password");
    exit;
}
?>