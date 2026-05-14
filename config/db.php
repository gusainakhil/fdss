<?php
// Database Configuration
define('DB_HOST', '184.168.122.185');
define('DB_USER', 'fdssbeatleanalyt_User');
define('DB_PASS', '7aTb)XFVbOPF%o=T');
define('DB_NAME', 'fdssbeatleanalyt_Database');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
