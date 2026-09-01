<?php

require_once __DIR__ . '/../config/db.php';

function fdss_process_login(mysqli $conn, string $redirectPrefix = ''): string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return '';
    }

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        return 'Username and password are required.';
    }

    $query = 'SELECT user_id, username, password_hash, role, status FROM fdss_users WHERE username = ?';
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        return 'Unable to process login request right now.';
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $stmt->close();
        return 'Invalid username or password.';
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password_hash'])) {
        return 'Invalid username or password.';
    }

    if ($user['status'] !== 'Active') {
        return 'Your account is inactive. Please contact administrator.';
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();

    if ($user['role'] === 'SUPER_ADMIN' || $user['role'] === 'ADMIN') {
        header('Location: ' . $redirectPrefix . 'admin/index.php');
        exit;
    }

    if ($user['role'] === 'ORG_ADMIN') {
        header('Location: ' . $redirectPrefix . 'index.php');
        exit;
    }

    return 'Your role (' . $user['role'] . ') is not correct to access this system. Only ORG_ADMIN can access the main dashboard.';
}
