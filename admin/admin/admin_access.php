<?php

require_once dirname(__DIR__, 2) . '/session_security.php';

function require_admin_access()
{
    start_secure_session();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Forbidden');
    }
}

function admin_csrf_token()
{
    if (!isset($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function require_admin_csrf_token()
{
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST'
        || !is_string($token)
        || !isset($_SESSION['admin_csrf_token'])
        || !hash_equals($_SESSION['admin_csrf_token'], $token)
    ) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Invalid request token.');
    }
}

function delete_admin_product($connection, $product_id)
{
    $image_statement = mysqli_prepare($connection, 'SELECT product_image FROM products WHERE product_id = ?');
    if (
        $image_statement === false
        || !mysqli_stmt_bind_param($image_statement, 'i', $product_id)
        || !mysqli_stmt_execute($image_statement)
    ) {
        throw new RuntimeException('Unable to retrieve the product image.');
    }

    $image_name = null;
    if (!mysqli_stmt_bind_result($image_statement, $image_name)) {
        throw new RuntimeException('Unable to read the product image.');
    }
    $has_image = mysqli_stmt_fetch($image_statement);
    if ($has_image === false) {
        mysqli_stmt_close($image_statement);
        throw new RuntimeException('Unable to fetch the product image.');
    }
    if ($has_image !== true) {
        mysqli_stmt_close($image_statement);
        http_response_code(404);
        exit('Product not found.');
    }
    mysqli_stmt_close($image_statement);

    $delete_statement = mysqli_prepare($connection, 'DELETE FROM products WHERE product_id = ?');
    if (
        $delete_statement === false
        || !mysqli_stmt_bind_param($delete_statement, 'i', $product_id)
        || !mysqli_stmt_execute($delete_statement)
    ) {
        throw new RuntimeException('Unable to delete the requested product.');
    }
    if (mysqli_stmt_affected_rows($delete_statement) !== 1) {
        mysqli_stmt_close($delete_statement);
        http_response_code(404);
        exit('Product not found.');
    }
    mysqli_stmt_close($delete_statement);

    if ($image_name !== null && $image_name !== '') {
        $image_directory = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'product_images');
        $image_path = $image_directory === false
            ? false
            : realpath($image_directory . DIRECTORY_SEPARATOR . basename($image_name));
        if (
            $image_path !== false
            && strpos($image_path, $image_directory . DIRECTORY_SEPARATOR) === 0
            && !unlink($image_path)
        ) {
            throw new RuntimeException('The product was deleted, but its image could not be removed.');
        }
    }
}
