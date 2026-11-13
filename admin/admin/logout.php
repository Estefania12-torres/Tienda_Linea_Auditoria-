<?php

require_once __DIR__ . '/admin_access.php';
require_admin_access();
require_admin_csrf_token();

$_SESSION = array();
session_destroy();
header('Location: ../login.php');
exit;
