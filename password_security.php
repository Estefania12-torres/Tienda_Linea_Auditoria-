<?php

function hash_project_password($password)
{
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($hash === false) {
        throw new RuntimeException('Unable to hash the password.');
    }

    return $hash;
}

function verify_and_upgrade_password($connection, $table, $id_column, $id, $stored_password, $provided_password)
{
    $password_info = password_get_info($stored_password);
    if ($password_info['algoName'] !== 'unknown') {
        if (!password_verify($provided_password, $stored_password)) {
            return false;
        }

        if (!password_needs_rehash($stored_password, PASSWORD_DEFAULT)) {
            return true;
        }
    } elseif (preg_match('/^[a-f0-9]{32}$/i', $stored_password)) {
        if (!hash_equals(strtolower($stored_password), md5($provided_password))) {
            return false;
        }
    } elseif (!hash_equals($stored_password, $provided_password)) {
        return false;
    }

    $allowed_columns = array(
        'admin_info' => array('admin_id', 'admin_password'),
        'register' => array('email', 'password'),
        'user_info' => array('user_id', 'password'),
    );

    if (!isset($allowed_columns[$table]) || $allowed_columns[$table][0] !== $id_column) {
        throw new InvalidArgumentException('Unsupported password table or identifier.');
    }

    $new_hash = hash_project_password($provided_password);
    $statement = mysqli_prepare(
        $connection,
        'UPDATE `' . $table . '` SET `' . $allowed_columns[$table][1] . '` = ? WHERE `' . $id_column . '` = ?'
    );
    if ($statement === false) {
        throw new RuntimeException('Unable to prepare the password upgrade.');
    }

    if (!mysqli_stmt_bind_param($statement, 'ss', $new_hash, $id) || !mysqli_stmt_execute($statement)) {
        $error = mysqli_stmt_error($statement);
        mysqli_stmt_close($statement);
        throw new RuntimeException('Unable to upgrade the stored password: ' . $error);
    }

    mysqli_stmt_close($statement);

    if ($table === 'user_info') {
        $backup_statement = mysqli_prepare($connection, 'UPDATE user_info_backup SET password = ? WHERE user_id = ?');
        if ($backup_statement === false) {
            throw new RuntimeException('Unable to prepare the backup password upgrade.');
        }

        if (!mysqli_stmt_bind_param($backup_statement, 'ss', $new_hash, $id) || !mysqli_stmt_execute($backup_statement)) {
            $error = mysqli_stmt_error($backup_statement);
            mysqli_stmt_close($backup_statement);
            throw new RuntimeException('Unable to upgrade the backup password: ' . $error);
        }
        mysqli_stmt_close($backup_statement);
    }

    return true;
}
