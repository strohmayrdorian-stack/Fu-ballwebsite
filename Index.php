<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();

$fehler = "";
$con = mysqli_connect("localhost", "root", "", "FBV");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['login'])) {

    $user_in = $_POST['user'] ?? '';
    $pass_in = $_POST['pass'] ?? '';

    $stmt = mysqli_prepare(
            $con,
            "SELECT password FROM login WHERE username = ?"
    );

    mysqli_stmt_bind_param($stmt, "s", $user_in);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        if (password_verify($pass_in, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['user'] = $user_in;
            header("Location: dashboard.php");
            exit;
        } else {
            $fehler = "⚠️ Benutzername oder Passwort falsch!";
        }

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

        .login-box input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .login-box button,
        .register-btn {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .login-box button {
            background: #0b6623;
            color: white;
            border: none;
        }

        .login-box button:hover {
            background: #094d1b;
        }

        .register-btn {
            display: block;
            background: #1fa84f;
            color: white;
            text-decoration: none;
        }

        .register-btn:hover {
            background: #17853d;
        }

        .fehler {
            color: red;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 10px;
            font-size: 12px;
        }
    </style>
</head>

<body>

<div class="login-box">
    <h1>⚽ Fußball Login</h1>


    <?php if ($fehler): ?>
        <div class="fehler"><?= $fehler ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="user" placeholder="Benutzername" required>
        <input type="password" name="pass" placeholder="Passwort" required>
        <button type="submit" name="login">Einloggen</button>
    </form>

    <a href="register.php" class="register-btn">Registrieren</a>

    <div class="footer">
        © <?= date("Y") ?> Fußballclub
    </div>
</div>

</body>
</html>