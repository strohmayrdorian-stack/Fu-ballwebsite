<?php
session_start();

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$con = mysqli_connect("localhost", "root", "", "FBV");
if (!$con) {
    die("Datenbankverbindung fehlgeschlagen");
}

$res = mysqli_query(
    $con,
    "SELECT * FROM liga ORDER BY punkte DESC, (tore - gegentore) DESC"
);
?>

<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Fußballtabelle</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #0b6623, #1fa84f);
    margin: 0;
    padding: 30px;
}

.container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    max-width: 1000px;
    margin: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

h1 {
    text-align: center;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    text-align: center;
}

th {
    background: #0b6623;
    color: white;
}

tr:nth-child(even) {
    background: #f2f2f2;
}

td a {
    color: black;
    text-decoration: none;
    font-weight: bold;
}

td img {
    vertical-align: middle;
    margin-right: 6px;
}

.logout {
    text-align: right;
    margin-bottom: 10px;
}

.logout a {
    text-decoration: none;
    color: red;
    font-weight: bold;
}

.admin {
    text-align: right;
    margin-top: 15px;
}
</style>
</head>

<body>

<div class="container">

<div class="logout">
    <a href="logout.php">Logout</a>
</div>

<h1>⚽ Fußball Tabelle</h1>

<table>
<tr>
    <th>#</th>
    <th>Team</th>
    <th>Sp</th>
    <th>S</th>
    <th>U</th>
    <th>N</th>
    <th>T</th>
    <th>GT</th>
    <th>P</th>
</tr>

<?php $platz = 1; ?>
<?php while ($row = mysqli_fetch_assoc($res)): ?>
<tr>
    <td><?= $platz ?></td>

    <td style="text-align:left">
        <a href="team.php?id=<?= $row['id'] ?>">
            <img src="images/teams/<?= htmlspecialchars($row['logo']) ?>" height="24">
            <?= htmlspecialchars($row['team']) ?>
        </a>
    </td>

    <td><?= $row['spiele'] ?></td>
    <td><?= $row['siege'] ?></td>
    <td><?= $row['unentschieden'] ?></td>
    <td><?= $row['niederlagen'] ?></td>
    <td><?= $row['tore'] ?></td>
    <td><?= $row['gegentore'] ?></td>
    <td><strong><?= $row['punkte'] ?></strong></td>
</tr>
<?php $platz++; endwhile; ?>

</table>

<div class="admin">
    <a href="admin.php">⚙ Adminbereich</a>
</div>

</div>

</body>
</html>
