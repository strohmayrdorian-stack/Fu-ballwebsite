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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "
SELECT
    l.id,
    l.team,
    l.logo,
    l.trainer,
    l.gruendung,
    l.stadion,

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
WHERE l.id = ?
GROUP BY l.id, l.team, l.logo, l.trainer, l.gruendung, l.stadion
";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$team = mysqli_fetch_assoc($res);

if (!$team) {
    die("Team nicht gefunden");
}

/* Letzte 5 Spiele + Form */
$form = [];
$last_matches = [];

$sql_last = "
SELECT
    s.datum,
    s.uhrzeit,
    s.heim_id,
    s.gast_id,
    s.heim_tore,
    s.gast_tore,
    h.team AS heim_team,
    g.team AS gast_team
FROM spiele s
JOIN liga h ON h.id = s.heim_id
JOIN liga g ON g.id = s.gast_id
WHERE (s.heim_id = ? OR s.gast_id = ?)
  AND s.heim_tore IS NOT NULL
  AND s.gast_tore IS NOT NULL
ORDER BY s.datum DESC, s.uhrzeit DESC
LIMIT 5
";

$stmt2 = mysqli_prepare($con, $sql_last);
mysqli_stmt_bind_param($stmt2, "ii", $id, $id);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);

while ($m = mysqli_fetch_assoc($res2)) {
    $last_matches[] = $m;

    if ($m['heim_id'] == $id) {
        if ($m['heim_tore'] > $m['gast_tore']) $form[] = 'W';
        elseif ($m['heim_tore'] == $m['gast_tore']) $form[] = 'D';
        else $form[] = 'L';
    } else {
        if ($m['gast_tore'] > $m['heim_tore']) $form[] = 'W';
        elseif ($m['gast_tore'] == $m['heim_tore']) $form[] = 'D';
        else $form[] = 'L';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($team['team']) ?></title>

    <style>
        body {
            background: linear-gradient(135deg, #0b6623, #1fa84f);
            font-family: Arial, sans-serif;
            padding: 30px;
            margin: 0;
        }

        .card {
            background: white;
            max-width: 1100px;
            margin: auto;
            padding: 30px 45px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header img {
            height: 120px;
            object-fit: contain;
        }

        .header h1 {
            margin: 15px 0 10px;
            font-size: 54px;
        }

        .header p {
            color: #555;
            font-size: 18px;
            margin: 0;
        }

        .stats div {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e6e6e6;
            font-size: 18px;
        }

        .stats strong {
            font-size: 20px;
        }

        .form {
            margin-top: 12px;
        }

        .form span {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .W { background: #198754; }
        .D { background: #f0ad4e; }
        .L { background: #dc3545; }

        .matches {
            margin-top: 25px;
        }
        .btn-back {
            display: inline-block;
            padding: 10px 18px;
            background: #1fa84f;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .btn-back:hover {
            background: #15803d;
            transform: translateY(-2px);
        }
        .match-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 17px;
        }

        .back {
            margin-top: 25px;
            text-align: center;
        }

        .back a {
            color #FFFF;
            font-size: 22px;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="header">
        <?php if (!empty($team['logo'])): ?>
            <img src="images/teams/<?= htmlspecialchars($team['logo']) ?>" alt="">
        <?php endif; ?>
        <h1><?= htmlspecialchars($team['team']) ?></h1>
        <p><?= htmlspecialchars($team['stadion'] ?? '') ?></p>
    </div>

    <div class="stats">
        <div><span>Trainer</span><span><?= htmlspecialchars($team['trainer'] ?? '') ?></span></div>
        <div><span>Gegründet</span><span><?= htmlspecialchars($team['gruendung'] ?? '') ?></span></div>
        <div><span>Spiele</span><span><?= (int)$team['spiele'] ?></span></div>
        <div><span>Siege</span><span><?= (int)$team['siege'] ?></span></div>
        <div><span>Unentschieden</span><span><?= (int)$team['unentschieden'] ?></span></div>
        <div><span>Niederlagen</span><span><?= (int)$team['niederlagen'] ?></span></div>
        <div><span>Tore</span><span><?= (int)$team['tore'] ?></span></div>
        <div><span>Gegentore</span><span><?= (int)$team['gegentore'] ?></span></div>
        <div><strong>Punkte</strong><strong><?= (int)$team['punkte'] ?></strong></div>
    </div>

    <h2 style="margin-top:30px;">Letzte Spiele</h2>

    <div class="form">
        <?php if (count($form) > 0): ?>
            <?php foreach ($form as $f): ?>
                <span class="<?= $f ?>"><?= $f ?></span>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Noch keine beendeten Spiele vorhanden.</p>
        <?php endif; ?>
    </div>

    <div class="matches">
        <?php foreach ($last_matches as $m): ?>
            <div class="match-row">
                <?= htmlspecialchars($m['datum']) ?> <?= htmlspecialchars($m['uhrzeit']) ?> —
                <strong><?= htmlspecialchars($m['heim_team']) ?></strong>
                <?= (int)$m['heim_tore'] ?> : <?= (int)$m['gast_tore'] ?>
                <strong><?= htmlspecialchars($m['gast_team']) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="back">
        <a href="dashboard.php" class="btn-back">← Zurück</a>

    </div>

</div>
<footer style="margin-top:30px; padding-top:15px; border-top:1px solid #ddd; text-align:center; color:#FFFF; font-size:14px;">
    © <?= date("Y") ?> Fußballverwaltung - Fasching - Strohmayr
</footer>
</body>
</html>