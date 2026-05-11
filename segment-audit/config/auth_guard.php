<?php
// auth_guard.php — include at top of ANY protected file.
// Detects if caller is an API or a page and responds accordingly.
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    // Check if request expects JSON (API calls) or HTML (page loads)
    $wantsJson = (
        isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    );

    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    } else {
        // Determine correct login path based on calling file depth
        $depth = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__), DIRECTORY_SEPARATOR);
        $prefix = str_repeat('../', max(0, $depth - 2));
        header("Location: {$prefix}auth/login.php");
    }
    exit;
}

$CURRENT_USER_ID   = $_SESSION['user_id'];
$CURRENT_USER_NAME = $_SESSION['user_name'] ?? '';
?>