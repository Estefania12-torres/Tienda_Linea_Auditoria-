<?php

function load_project_env()
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $env_file = __DIR__ . '/.env';

    if (!is_file($env_file)) {
        return;
    }

    $lines = file($env_file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Unable to read the project .env file.');
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $separator = strpos($line, '=');
        if ($separator === false) {
            throw new UnexpectedValueException('Invalid line in the project .env file.');
        }

        $name = trim(substr($line, 0, $separator));
        $value = trim(substr($line, $separator + 1));

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new UnexpectedValueException('Invalid variable name in the project .env file.');
        }

        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[strlen($value) - 1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function project_env($name)
{
    load_project_env();
    $value = getenv($name);

    if ($value === false) {
        throw new RuntimeException('Required environment variable is not set: ' . $name);
    }

    return $value;
}
