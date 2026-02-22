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

// Filter: all | upcoming | finished
$filter = $_GET['filter'] ?? 'all';
$filter = in_array($filter, ['all','upcoming','finished']) ? $filter : 'all';

// SQL: Spiele + Teamdaten (Name + Logo) holen
$sql = "
SELECT 
    s.id,
    s.datum,
    s.uhrzeit,
    s.heim_tore,
    s.gast_tore,

    h.id AS heim_id,
    h.team AS heim_team,
    h.logo AS heim_logo,

    g.id AS gast_id,
    g.team AS gast_team,
    g.logo AS gast_logo

FROM spiele s
JOIN liga h ON h.id = s.heim_id
JOIN liga g ON g.id = s.gast_id
";

// Filterbedingungen
if ($filter === 'upcoming') {
    // kommende: Ergebnis noch nicht gesetzt ODER Zeitpunkt in Zukunft
    $sql .= " WHERE (s.heim_tore IS NULL OR s.gast_tore IS NULL) ";
} elseif ($filter === 'finished') {
    $sql .= " WHERE (s.heim_tore IS NOT NULL AND s.gast_tore IS NOT NULL) ";
}

// Sortierung: kommende zuerst, dann nach Datum/Uhrzeit
$sql .= " ORDER BY s.datum ASC, s.uhrzeit ASC";

$res = mysqli_query($con, $sql);
if (!$res) {
    die("SQL Fehler: " . mysqli_error($con));
}
?>

<?php if (isset($_SESSION['user']) && $_SESSION['user'] === 'admin'): ?>
    <div class="admin">
        <a href="admin.php">⚙ Adminbereich</a>
    </div>
<?php endif; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Spielplan</title>

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

        h1 { text-align: center; margin-bottom: 10px; }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .topbar a {
            text-decoration: none;
            font-weight: bold;
        }

        .back a { color: #0b6623; }
        .logout a { color: red; }

        .filters a {
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid #ddd;
            color: black;
            margin-right: 6px;
            display: inline-block;
        }

        .filters a.active {
            background: #0b6623;
            color: white;
            border-color: #0b6623;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: center; }
        th { background: #0b6623; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }

        .teamcell {
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .teamcell img {
            height: 24px;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 999px;
            background: #eee;
            font-size: 12px;
            display: inline-block;
        }
        .badge.finished { background: #dff5e3; }
        .badge.upcoming { background: #fff2cc; }

        .score {
            font-weight: bold;
            font-size: 16px;
        }

        .empty {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</head>

<body>
<div class="container">

    <div class="topbar">
        <div class="back">
            <a href="dashboard.php">← Zur Tabelle</a>
        </div>

        <div class="filters">
            <a class="<?= $filter==='all'?'active':'' ?>" href="spiele.php?filter=all">Alle</a>
            <a class="<?= $filter==='upcoming'?'active':'' ?>" href="spiele.php?filter=upcoming">Kommend</a>
            <a class="<?= $filter==='finished'?'active':'' ?>" href="spiele.php?filter=finished">Beendet</a>
        </div>

        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <h1>📅 Spielplan</h1>

    <table>
        <tr>
            <th>Datum</th>
            <th>Zeit</th>
            <th>Heim</th>
            <th></th>
            <th>Gast</th>
            <th>Status</th>
        </tr>

        <?php if (mysqli_num_rows($res) === 0): ?>
            <tr><td class="empty" colspan="6">Keine Spiele gefunden.</td></tr>
        <?php endif; ?>

        <?php while ($row = mysqli_fetch_assoc($res)): ?>
            <?php
            $finished = ($row['heim_tore'] !== null && $row['gast_tore'] !== null);
            $score = $finished ? ($row['heim_tore'] . " : " . $row['gast_tore']) : "—";
            ?>
            <tr>
                <td><?= htmlspecialchars($row['datum']) ?></td>
                <td><?= htmlspecialchars($row['uhrzeit']) ?></td>

                <td>
                    <div class="teamcell">
                        <img src="images/teams/<?= htmlspecialchars($row['heim_logo']) ?>" alt="">
                        <a href="team.php?id=<?= (int)$row['heim_id'] ?>" style="color:black; text-decoration:none; font-weight:bold;">
                            <?= htmlspecialchars($row['heim_team']) ?>
                        </a>
                    </div>
                </td>

                <td class="score"><?= htmlspecialchars($score) ?></td>

                <td>
                    <div class="teamcell">
                        <img src="images/teams/<?= htmlspecialchars($row['gast_logo']) ?>" alt="">
                        <a href="team.php?id=<?= (int)$row['gast_id'] ?>" style="color:black; text-decoration:none; font-weight:bold;">
                            <?= htmlspecialchars($row['gast_team']) ?>
                        </a>
                    </div>
                </td>

                <td>
                    <?php if ($finished): ?>
                        <span class="badge finished">Beendet</span>
                    <?php else: ?>
                        <span class="badge upcoming">Kommend</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</div>
</body>
</html>
