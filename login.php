<?php

session_start();

require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        die("لطفاً ایمیل و رمز عبور را وارد کنید.");
    }

    $stmt = $pdo->prepare(
        "SELECT id, name, email, password
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        $_SESSION["user_email"] = $user["email"];

        header("Location: chat.php");
        exit;

    } else {

        die("ایمیل یا رمز عبور اشتباه است.");

    }
}