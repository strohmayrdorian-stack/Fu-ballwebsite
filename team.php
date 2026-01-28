<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$con = mysqli_connect("localhost", "root", "", "FBV");
$id = $_GET['id'] ?? 0;

$stmt = mysqli_prepare($con, "SELECT * FROM liga WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$team = mysqli_fetch_assoc($res);

if (!$team) die("Team nicht gefunden");
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($team['team']) ?></title>

<style>
body {
    background: linear-gradient(135deg, #0b6623, #1fa84f);
    font-family: Arial;
    padding: 30px;
}

.card {
    background: white;
    max-width: 700px;
    margin: auto;
    padding: 30px;
    border-radius: 15px;
}

.header {
    text-align: center;
}

.header img {
    height: 100px;
}

.stats div {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.form span {
    padding: 6px 10px;
    border-radius: 6px;
    color: white;
    font-weight: bold;
}

.W { background: green; }
.D { background: orange; }
.L { background: red; }
</style>
</head>

<body>

<div class="card">

<div class="header">
    <img src="images/teams/<?= $team['logo'] ?>">
    <h1><?= htmlspecialchars($team['team']) ?></h1>
    <p><?= $team['stadion'] ?></p>
</div>

<div class="stats">
    <div><span>Trainer</span><span><?= $team['trainer'] ?></span></div>
    <div><span>Gegründet</span><span><?= $team['gruendung'] ?></span></div>
    <div><span>Spiele</span><span><?= $team['spiele'] ?></span></div>
    <div><span>Siege</span><span><?= $team['siege'] ?></span></div>
    <div><span>Unentschieden</span><span><?= $team['unentschieden'] ?></span></div>
    <div><span>Niederlagen</span><span><?= $team['niederlagen'] ?></span></div>
    <div><span>Tore</span><span><?= $team['tore'] ?></span></div>
    <div><span>Gegentore</span><span><?= $team['gegentore'] ?></span></div>
    <div><strong>Punkte</strong><strong><?= $team['punkte'] ?></strong></div>
</div>

<h3>Letzte Spiele</h3>
<div class="form">
<?php
foreach (str_split($team['form']) as $f) {
    echo "<span class='$f'>$f</span> ";
}
?>
</div>

<p style="margin-top:20px;text-align:center;">
<a href="dashboard.php">⬅ Zurück</a>
</p>

</div>

</body>
</html>
