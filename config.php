<?php
// Where files live on disk (server path) — absolute paths
define('UPLOAD_DIR', __DIR__ . '/uploads/'); //we define a variable called UPLOAD_DIR and then __DIR__ point to where we are at rn 
define('UPLOAD_DIR_USERS', UPLOAD_DIR . 'users/');
define('UPLOAD_DIR_QUESTIONS', UPLOAD_DIR . 'questions/');
define('UPLOAD_DIR_RECOMMENDED', UPLOAD_DIR . 'recommended/');

// How the browser reaches them (URL path).
define('UPLOAD_URL', '/SparkWebTwoProject/uploads/'); //The public path for the browser to access our photos
define('UPLOAD_URL_USERS', UPLOAD_URL . 'users/');
define('UPLOAD_URL_QUESTIONS', UPLOAD_URL . 'questions/');
define('UPLOAD_URL_RECOMMENDED', UPLOAD_URL . 'recommended/');

// Default user image 
define('DEFAULT_USER_PHOTO', 'Defaultavatar.jpg');
?>
