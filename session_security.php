<?php

require_once __DIR__ . '/env.php';
load_project_env();

function start_secure_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure_setting = getenv('SESSION_SECURE_COOKIE');
    $is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' && $_SERVER['HTTPS'] !== '';
    $is_https = $is_https || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $secure_cookie = $secure_setting === false || $secure_setting === ''
        ? $is_https
        : in_array(strtolower($secure_setting), array('1', 'true', 'yes', 'on'), true);

    if (ini_set('session.use_strict_mode', '1') === false || ini_set('session.use_only_cookies', '1') === false) {
        throw new RuntimeException('Unable to enable secure PHP session settings.');
    }

    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure_cookie,
        'httponly' => true,
        'samesite' => 'Strict',
    ));

    session_start();

    $now = time();
    $last_activity = isset($_SESSION['_session_last_activity'])
        ? (int) $_SESSION['_session_last_activity']
        : $now;
    $created_at = isset($_SESSION['_session_created_at'])
        ? (int) $_SESSION['_session_created_at']
        : $now;

    if ($now - $last_activity > 900 || $now - $created_at > 28800) {
        $_SESSION = array();
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Unable to renew an expired session.');
        }
        $created_at = $now;
    }

    $_SESSION['_session_created_at'] = $created_at;
    $_SESSION['_session_last_activity'] = $now;
}

function login_attempt_store($bucket, $operation)
{
    $client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $client_key = hash('sha256', $bucket . "\0" . $client_ip);
    $store_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tienda-linea-login-attempts.json';
    $store = fopen($store_path, 'c+');

    if ($store === false) {
        throw new RuntimeException('Unable to open the login rate-limit store.');
    }

    try {
        if (!flock($store, LOCK_EX)) {
            throw new RuntimeException('Unable to lock the login rate-limit store.');
        }

        rewind($store);
        $contents = stream_get_contents($store);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the login rate-limit store.');
        }

        $attempts = $contents === '' ? array() : json_decode($contents, true);
        if (!is_array($attempts)) {
            throw new UnexpectedValueException('The login rate-limit store contains invalid data.');
        }

        $now = time();
        foreach ($attempts as $key => $state) {
            if (
                !is_array($state)
                || !isset($state['started_at'], $state['count'])
                || $now - (int) $state['started_at'] >= 900
            ) {
                unset($attempts[$key]);
            }
        }

        $result = $operation($attempts, $client_key, $now);
        $encoded = json_encode($attempts);
        if (
            $encoded === false
            || !rewind($store)
            || !ftruncate($store, 0)
            || fwrite($store, $encoded) !== strlen($encoded)
        ) {
            throw new RuntimeException('Unable to update the login rate-limit store.');
        }

        fflush($store);
        flock($store, LOCK_UN);
        return $result;
    } finally {
        fclose($store);
    }
}

function begin_login_attempt($bucket)
{
    $state = login_attempt_store($bucket, function (&$attempts, $client_key, $now) {
        if (isset($attempts[$client_key]) && (int) $attempts[$client_key]['count'] >= 5) {
            return array(false, max(1, (int) $attempts[$client_key]['started_at'] + 900 - $now));
        }

        if (!isset($attempts[$client_key])) {
            $attempts[$client_key] = array('started_at' => $now, 'count' => 0);
        }
        $attempts[$client_key]['count']++;

        return array(true, 0);
    });

    if (!$state[0]) {
        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        error_log('[login-rate-limit] Request blocked for ' . $bucket . ' (client=' . substr(hash('sha256', $client_ip), 0, 16) . ')');
        http_response_code(429);
        header('Retry-After: ' . $state[1]);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Too many login attempts. Please try again later.');
    }
}

function record_login_attempt($bucket, $successful)
{
    login_attempt_store($bucket, function (&$attempts, $client_key) use ($bucket, $successful) {
        if ($successful) {
            unset($attempts[$client_key]);
            return;
        }

        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        error_log('[login-rate-limit] Failed authentication for ' . $bucket . ' (client=' . substr(hash('sha256', $client_ip), 0, 16) . ')');
    });
}
