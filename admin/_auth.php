<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['SUPER_ADMIN', 'ADMIN'], true)) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

mysqli_report(MYSQLI_REPORT_OFF);

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_count($conn, $sql, $types = '', ...$params)
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int) ($row['total'] ?? 0);
}
