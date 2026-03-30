<?php

require_once __DIR__ . '/../db.php';

function login_user(string $email, string $password): bool
{
    $conn = db_connect();
    $stmt = $conn->prepare('SELECT Id_users, email, mot_de_passe FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $isValid = false;

    if ($user) {
        $stored = $user['mot_de_passe'];
        $isHashed = password_get_info($stored)['algo'] !== null;
        $isValid = $isHashed ? password_verify($password, $stored) : hash_equals($stored, $password);

        if ($isValid) {
            $_SESSION['user'] = [
                'id' => (int)$user['Id_users'],
                'email' => $user['email']
            ];
        }
    }

    $stmt->close();
    $conn->close();

    return $isValid;
}
