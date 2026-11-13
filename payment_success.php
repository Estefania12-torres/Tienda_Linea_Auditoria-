<?php

require_once __DIR__ . '/session_security.php';
start_secure_session();

if (
    !isset($_SESSION['uid'])
    || !isset($_SESSION['role'])
    || $_SESSION['role'] !== 'user'
) {
    http_response_code(401);
    exit('Authentication required.');
}

$session_user_id = (string) $_SESSION['uid'];
$callback_user_id = isset($_GET['cm']) && is_scalar($_GET['cm'])
    ? (string) $_GET['cm']
    : '';

if ($callback_user_id === '' || !hash_equals($session_user_id, $callback_user_id)) {
    http_response_code(403);
    exit('Payment user does not match the authenticated session.');
}

if (!isset($_GET['st']) || !is_string($_GET['st']) || $_GET['st'] !== 'Completed') {
    http_response_code(400);
    exit('Invalid payment status.');
}

http_response_code(503);
exit('Payment verification is not configured; no order was recorded.');
