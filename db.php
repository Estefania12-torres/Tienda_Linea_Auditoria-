<?php

require_once __DIR__ . '/env.php';

$servername = project_env('SHOP_DB_HOST');
$username = project_env('SHOP_DB_USER');
$password = project_env('SHOP_DB_PASSWORD');
$db = project_env('SHOP_DB_NAME');

// Create connection
$con = mysqli_connect($servername, $username, $password,$db);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}


?>
