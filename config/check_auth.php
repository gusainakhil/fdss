<?php
// Session Security Check
// Include this file at the top of any page that requires authentication

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check session timeout (30 minutes)
$timeout = 30 * 60; // 30 minutes in seconds
if (isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] > $timeout) {
    session_destroy();
    header('Location: ../login.php?timeout=true');
    exit;
}

// Refresh login time
$_SESSION['login_time'] = time();

// Define role-based access control
$allowed_roles = array();

function check_role($required_roles = array()) {
    if (empty($required_roles)) {
        return true;
    }
    
    if (!is_array($required_roles)) {
        $required_roles = array($required_roles);
    }
    
    if (!in_array($_SESSION['role'], $required_roles)) {
        header('Location: ../login.php?access=denied');
        exit;
    }
    
    return true;
}
?>
