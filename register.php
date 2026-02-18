<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

$meldung = "";
$fehler = "";

$con = mysqli_connect("localhost", "root", "", "FBV");

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['register'])) {

    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';

    if (empty($user) || empty($pass)) {
        $fehler = "⚠️ Bitte alle Felder ausfüllen.";
    }
    elseif (strlen($pass) < 4) {
        $fehler = "⚠️ Passwort muss mindestens 4 Zeichen haben.";
    }
    else {

        /* Prüfen ob Benutzer bereits existiert */
        $check = mysqli_prepare(
                $con,
                "SELECT id FROM login WHERE username = ?"
        );

        mysqli_stmt_bind_param($check, "s", $user);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $fehler = "⚠️ Benutzername existiert bereits.";
        } else {

            $hash = password_hash($pass, PASSWORD_DEFAULT);

            /* Rolle direkt mit speichern */
            $stmt = mysqli_prepare(
                    $con,
                    "INSERT INTO login (username, password, role) VALUES (?, ?, 'user')"
            );

            mysqli_stmt_bind_param($stmt, "ss", $user, $hash);

            if (mysqli_stmt_execute($stmt)) {
                $meldung = "✅ Registrierung erfolgreich! Du kannst dich jetzt einloggen.";
            } else {
                $fehler = "⚠️ Fehler bei der Registrierung.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrieren</title>

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

        .box {
            background: white;
            padding: 30px;
            width: 320px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }

        .box input {
            width: 92.5%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .box button {
            width: 100%;
            padding: 12px;
            background: #0b6623;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .box button:hover {
            background: #094d1b;
        }

        .message {
            color: green;
            margin-bottom: 10px;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .back {
            display: block;
            margin-top: 10px;
            text-decoration: none;
            color: #0b6623;
        }
    </style>
</head>

<body>

<div class="box">
    <h1>⚽ Registrieren</h1>

    <?php if ($meldung): ?>
        <div class="message"><?= htmlspecialchars($meldung) ?></div>
    <?php endif; ?>

    <?php if ($fehler): ?>
        <div class="error"><?= htmlspecialchars($fehler) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="user" placeholder="Benutzername" required>
        <input type="password" name="pass" placeholder="Passwort" required>
        <button type="submit" name="register">Registrieren</button>
    </form>

    <a href="index.php" class="back">⬅ Zurück zum Login</a>
</div>

</body>
</html>
