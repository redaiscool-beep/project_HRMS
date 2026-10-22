<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$username = $argv[1] ?? '';
$password = $argv[2] ?? '';

if ($username === '' || $password === '') {
    exit("Usage: php scripts/create_user.php <username> <password>\n");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = db()->prepare(
    'INSERT INTO train_users (username, password_hash)
     VALUES (:username, :password_hash)
     ON CONFLICT (username)
     DO UPDATE SET password_hash = EXCLUDED.password_hash'
);

$stmt->execute([
    'username' => $username,
    'password_hash' => $passwordHash,
]);

echo "User created or updated: {$username}\n";