<?php

// Simple DB creator for PostgreSQL using .env credentials.
// Connects to 'postgres' database, creates DB if missing.

function env_value(string $key, string $default = ''): string
{
    $envPath = __DIR__.'/../.env';
    if (! is_file($envPath)) {
        return $default;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        if (trim($k) === $key) {
            return trim(trim($v), "\"'");
        }
    }

    return $default;
}

$driver = env_value('DB_CONNECTION', 'pgsql');
if ($driver !== 'pgsql') {
    fwrite(STDOUT, "Only pgsql is supported by this helper.\n");
    exit(0);
}

$host = env_value('DB_HOST', '127.0.0.1');
$port = env_value('DB_PORT', '5432');
$db = env_value('DB_DATABASE', 'komeras_super_app');
$user = env_value('DB_USERNAME', 'postgres');
$pass = env_value('DB_PASSWORD', '');

try {
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Cannot connect to Postgres: {$e->getMessage()}\n");
    exit(1);
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = :db');
    $stmt->execute(['db' => $db]);
    if (! $stmt->fetch()) {
        $pdo->exec('CREATE DATABASE "'.str_replace('"', '\"', $db).'" WITH ENCODING \'UTF8\'');
        fwrite(STDOUT, "CREATED\n");
    } else {
        fwrite(STDOUT, "EXISTS\n");
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to create/check database: {$e->getMessage()}\n");
    exit(2);
}
