<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$fehler = "";
$con = mysqli_connect("localhost", "root", "", "FBV");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_in = $_POST['user'] ?? '';
$pass_in = $_POST['pass'] ?? '';

if (isset($_POST['login'])) {

    $stmt = mysqli_prepare(
        $con,
        "SELECT * FROM login WHERE username = ? AND password = ?"
    );

    mysqli_stmt_bind_param($stmt, "ss", $user_in, $pass_in);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        session_start();
        $_SESSION['login'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $fehler = "⚠️ Benutzername oder Passwort falsch!";
    }
}
?>


<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Fußball Login</title>

<style>
body {
    margin: 0;
    height: 100vh;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #0b6623, #1fa84f);
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    background: white;
    padding: 30px;
    width: 320px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    text-align: center;
}

.login-box h1 {
    margin-bottom: 10px;
}

.login-box p {
    margin-bottom: 20px;
    color: #555;
}

.login-box input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
}

.login-box button {
    width: 100%;
    padding: 12px;
    background: #0b6623;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}

.login-box button:hover {
    background: #094d1b;
}

.fehler {
    color: red;
    margin-bottom: 10px;
    font-size: 14px;
}

.footer {
    margin-top: 15px;
    font-size: 12px;
    color: #777;
}
</style>
</head>

<body>

<div class="login-box">
    <h1>⚽ Fußball Login</h1>
    <p>Willkommen zurück!</p>

    <?php if ($fehler): ?>
        <div class="fehler"><?= $fehler ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="user" placeholder="Benutzername" required>
        <input type="password" name="pass" placeholder="Passwort" required>
        <button type="submit" name="login">Einloggen</button>
    </form>

    <div class="footer">
        © <?= date("Y") ?> Fußballclub
    </div>
</div>

</body>
</html>
