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

$standings_sql = "
SELECT
  l.id,
  l.team,
  l.logo,

  COALESCE(SUM(CASE
    WHEN (s.heim_id = l.id OR s.gast_id = l.id)
     AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN 1
    ELSE 0
  END),0) AS spiele,

  COALESCE(SUM(CASE
    WHEN s.heim_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL AND s.heim_tore > s.gast_tore THEN 1
    WHEN s.gast_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL AND s.gast_tore > s.heim_tore THEN 1
    ELSE 0
  END),0) AS siege,

  COALESCE(SUM(CASE
    WHEN (s.heim_id = l.id OR s.gast_id = l.id)
     AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL
     AND s.heim_tore = s.gast_tore THEN 1
    ELSE 0
  END),0) AS unentschieden,

  COALESCE(SUM(CASE
    WHEN s.heim_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL AND s.heim_tore < s.gast_tore THEN 1
    WHEN s.gast_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL AND s.gast_tore < s.heim_tore THEN 1
    ELSE 0
  END),0) AS niederlagen,

  COALESCE(SUM(CASE
    WHEN s.heim_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.heim_tore
    WHEN s.gast_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.gast_tore
    ELSE 0
  END),0) AS tore,

  COALESCE(SUM(CASE
    WHEN s.heim_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.gast_tore
    WHEN s.gast_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.heim_tore
    ELSE 0
  END),0) AS gegentore,

  (COALESCE(SUM(CASE
    WHEN s.heim_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.heim_tore
    WHEN s.gast_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.gast_tore
    ELSE 0
  END),0)
  -
  COALESCE(SUM(CASE
    WHEN s.heim_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.gast_tore
    WHEN s.gast_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL THEN s.heim_tore
    ELSE 0
  END),0)) AS tordiff,

  COALESCE(SUM(CASE
    WHEN s.heim_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL AND s.heim_tore > s.gast_tore THEN 3
    WHEN s.gast_id = l.id AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL AND s.gast_tore > s.heim_tore THEN 3
    WHEN (s.heim_id = l.id OR s.gast_id = l.id)
     AND s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL
     AND s.heim_tore = s.gast_tore THEN 1
    ELSE 0
  END),0) AS punkte

FROM liga l
LEFT JOIN spiele s
  ON s.heim_id = l.id OR s.gast_id = l.id
GROUP BY l.id, l.team, l.logo
ORDER BY punkte DESC, tordiff DESC, tore DESC, l.team ASC
";

$res = mysqli_query($con, $standings_sql);
if (!$res) die("Standings Fehler: " . mysqli_error($con));
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

        .spiele {
            text-align: right;
            margin-top: 8px;
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
                    <a href="team.php?id=<?= (int)$row['id'] ?>">
                        <?php if (!empty($row['logo'])): ?>
                            <img src="images/teams/<?= htmlspecialchars($row['logo']) ?>" height="24" alt="">
                        <?php endif; ?>
                        <?= htmlspecialchars($row['team']) ?>
                    </a>
                </td>

                <td><?= (int)$row['spiele'] ?></td>
                <td><?= (int)$row['siege'] ?></td>
                <td><?= (int)$row['unentschieden'] ?></td>
                <td><?= (int)$row['niederlagen'] ?></td>
                <td><?= (int)$row['tore'] ?></td>
                <td><?= (int)$row['gegentore'] ?></td>
                <td><strong><?= (int)$row['punkte'] ?></strong></td>
            </tr>
            <?php $platz++; endwhile; ?>

    </table>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="admin">
            <a href="admin.php">⚙ Adminbereich</a>
        </div>
    <?php endif; ?>

    <div class="spiele">
        <a href="spiele.php">Spielplan</a>
    </div>

</div>

</body>
</html>