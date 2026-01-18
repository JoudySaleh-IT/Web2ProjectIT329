<?php
// ---- Enhanced error reporting ----
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) session_start(); //معالجه

// Use YOUR project files/paths
require __DIR__ . '/db_connect.php';      // provides $pdo (PDO)
require __DIR__ . '/config.php';          // defines UPLOAD_DIR_USERS, DEFAULT_USER_PHOTO
require __DIR__ . '/lib/upload_image.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit;
}

//1) Read inputs
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$email     = trim($_POST['emailAddress'] ?? ($_POST['email'] ?? ''));
$password  = $_POST['password'] ?? '';
$userType  = $_POST['userType'] ?? '';        // 'learner' or 'educator'
$topics    = $_POST['topics'] ?? [];          // optional array (educator)
//اذا فاضيه ؟؟ خلها فراغ  ، trim يحذف المسافات الزايده 
/* 2) Minimal validation */
if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || !in_array($userType, ['learner','educator'], true)) {
    header("Location: signup.php?error=Missing+or+invalid+fields");
    exit;
}
// يتأكد إن كل الحقول الأساسية موجودة ونوع المستخدم صحيح (Learner أو Educator).
/* 3) Duplicate email guard */
$chk = $pdo->prepare("SELECT id FROM User WHERE emailAddress = ?");
$chk->execute([$email]);
if ($chk->fetch()) {
    header("Location: signup.php?error=Email+already+exists");
    exit;
}

/* Additional validation for educators - must select at least one topic */
if ($userType === 'educator' && (empty($topics) || !is_array($topics))) {
    header("Location: signup.php?error=Educators+must+select+at+least+one+topic");
    exit;
}

/* 4) Optional profile image */
$photoFileName = DEFAULT_USER_PHOTO; // Default first

// Try profileImage first, then photo
if (!empty($_FILES['profileImage']['name'])) {
    $uploadedFileName = saveUploadedImage('profileImage', UPLOAD_DIR_USERS, 'usr');
    if ($uploadedFileName) {
        $photoFileName = $uploadedFileName;
    }
} elseif (!empty($_FILES['photo']['name'])) {
    $uploadedFileName = saveUploadedImage('photo', UPLOAD_DIR_USERS, 'usr');
    if ($uploadedFileName) {
        $photoFileName = $uploadedFileName;
    }
}
/*إذا المستخدم رفع صورة، يستدعي الدالة saveUploadedImage لحفظها ويخزن اسمها.
 الدالة تحفظ الصورة داخل مجلد الرفع (uploads/users) وتضيف بادئة usr للاسم عشان تكون فريدة.*/
        
/* 5) Hash password and insert user (PDO) */
$hash = password_hash($password, PASSWORD_DEFAULT);

$ins = $pdo->prepare("INSERT INTO User (firstName,lastName,emailAddress,password,photoFileName,userType)
                      VALUES (?,?,?,?,?,?)");
$ins->execute([$firstName, $lastName, $email, $hash, $photoFileName, $userType]);

$userId = (int)$pdo->lastInsertId();

/* 6) Auto-login */
$_SESSION['user_id']   = $userId;
$_SESSION['user_type'] = $userType;
$_SESSION['user_name'] = $firstName . ' ' . $lastName;
$_SESSION['user_photo'] = $photoFileName;

/* 7) Educator: create one quiz per selected topic (if any) */
if ($userType === 'educator' && is_array($topics) && !empty($topics)) {
    // First, we need to map topic names to topic IDs
    $topicMap = [
        'Math' => 1,
        'English' => 3, 
        'History' => 4,
    ];
    
    $insQ = $pdo->prepare("INSERT INTO Quiz (educatorID, topicID) VALUES (?, ?)");
    foreach ($topics as $topicName) {
        if (isset($topicMap[$topicName])) {
            $topicID = $topicMap[$topicName];
            $insQ->execute([$userId, $topicID]);
        }
    }
}

/* 8) Redirect to proper home */
header("Location: " . ($userType === 'educator' ? 'EducatorHomepage.php' : 'LearnerHomepage.php'));
exit;
?>