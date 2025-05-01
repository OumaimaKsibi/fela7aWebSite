<?php
session_start();
require_once '../config.php';
    $errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['email'])) {
        $errors['email'] = "L'e-mail est requis.";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'e-mail invalide.";
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email']
        ];
        $_SESSION['user_id'] = $user['id'];
                header("Location: ../../index.php");
        exit();
    } else {
        echo "Identifiants incorrects.";
    }
}
?>
