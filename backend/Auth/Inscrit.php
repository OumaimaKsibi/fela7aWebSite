<?php
session_start();
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $birthDate = $_POST['birth_date'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        echo "Les mots de passe ne correspondent pas.";
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        echo "Cet email est déjà utilisé.";
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insert = $pdo->prepare("INSERT INTO users 
        (first_name, last_name, birth_date, email, password)
        VALUES 
        (:first_name, :last_name, :birth_date, :email, :password)");

    $insert->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'birth_date' => $birthDate,
        'email' => $email,
        'password' => $hashedPassword,
    ]);

    // Get the new user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Store user in session (auto-login)
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'email' => $user['email']
    ];
    $_SESSION['user_id'] = $user['id'];

    header("Location: ../../index.php");
    exit();
}
?>
