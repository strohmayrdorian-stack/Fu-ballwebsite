<?php
session_start();
if (!isset($_SESSION['login'])) die("Kein Zugriff");

// OPTIONAL (besser): nur Admins
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') die("Kein Admin Zugriff");

$con = mysqli_connect("localhost", "root", "", "FBV");
if (!$con) die("Datenbankverbindung fehlgeschlagen");

$msg = "";

// Ansicht: teams | matches
$view = $_GET['view'] ?? 'teams';
if (!in_array($view, ['teams', 'matches'])) $view = 'teams';

/* =========================
   TEAMS: hinzufügen
========================= */
if (isset($_POST['add_team'])) {
    $team = trim($_POST['team'] ?? '');
    $logo = trim($_POST['logo'] ?? ''); // optional

    if ($team === '') {
        $msg = "Teamname darf nicht leer sein!";
    } else {
        // WICHTIG: Insert-Felder müssen zu deiner liga-Tabelle passen.
        // Ich nehme an: team, logo, punkte, tore, gegentore, spiele, siege, unentschieden, niederlagen existieren.
        $stmt = mysqli_prepare($con, "
            INSERT INTO liga (team, logo, punkte, tore, gegentore, spiele, siege, unentschieden, niederlagen)
            VALUES (?, ?, 0, 0, 0, 0, 0, 0, 0)
        ");
        mysqli_stmt_bind_param($stmt, "ss", $team, $logo);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Team hinzugefügt!";
        } else {
            $msg = "Fehler beim Hinzufügen: " . mysqli_error($con);
        }
    }
}

/* =========================
   TEAMS: bearbeiten
========================= */
if (isset($_POST['save_team'])) {
    $id = (int)$_POST['id'];
    $punkte = (int)$_POST['punkte'];
    $tore = (int)$_POST['tore'];
    $gegentore = (int)$_POST['gegentore'];

    $stmt = mysqli_prepare($con, "UPDATE liga SET punkte=?, tore=?, gegentore=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "iiii", $punkte, $tore, $gegentore, $id);
    mysqli_stmt_execute($stmt);

    $msg = "Team gespeichert!";
}

/* =========================
   TEAMS: löschen (nur wenn keine Matches)
========================= */
if (isset($_POST['delete_team'])) {
    $id = (int)$_POST['id'];

    // Prüfen ob Team in spiele vorkommt
    $stmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM spiele WHERE heim_id=? OR gast_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $count = (int)mysqli_fetch_assoc($r)['c'];

    if ($count > 0) {
        $msg = "Team kann nicht gelöscht werden: Es existieren noch Matches mit diesem Team.";
    } else {
        $stmt2 = mysqli_prepare($con, "DELETE FROM liga WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        $msg = "Team gelöscht!";
    }
}

/* =========================
   MATCHES: hinzufügen
========================= */
if (isset($_POST['add_match'])) {
    $heim_id = (int)$_POST['heim_id'];
    $gast_id = (int)$_POST['gast_id'];
    $datum = $_POST['datum'];
    $uhrzeit = $_POST['uhrzeit'];

    // Tore optional
    $heim_tore = ($_POST['heim_tore'] === '') ? null : (int)$_POST['heim_tore'];
    $gast_tore = ($_POST['gast_tore'] === '') ? null : (int)$_POST['gast_tore'];

    if ($heim_id === $gast_id) {
        $msg = "Heim und Gast dürfen nicht gleich sein!";
    } else {
        $stmt = mysqli_prepare($con, "
            INSERT INTO spiele (heim_id, gast_id, datum, uhrzeit, heim_tore, gast_tore)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "iissii", $heim_id, $gast_id, $datum, $uhrzeit, $heim_tore, $gast_tore);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Match hinzugefügt!";
        } else {
            $msg = "Fehler beim Match hinzufügen: " . mysqli_error($con);
        }
    }

    $view = 'matches';
}

/* =========================
   MATCHES: Ergebnis updaten
========================= */
if (isset($_POST['save_match'])) {
    $id = (int)$_POST['id'];
    $heim_tore = ($_POST['heim_tore'] === '') ? null : (int)$_POST['heim_tore'];
    $gast_tore = ($_POST['gast_tore'] === '') ? null : (int)$_POST['gast_tore'];

    $stmt = mysqli_prepare($con, "UPDATE spiele SET heim_tore=?, gast_tore=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "iii", $heim_tore, $gast_tore, $id);
    mysqli_stmt_execute($stmt);

    $msg = "Match gespeichert!";
    $view = 'matches';
}

/* =========================
   MATCHES: löschen
========================= */
if (isset($_POST['delete_match'])) {
    $id = (int)$_POST['id'];

    $stmt = mysqli_prepare($con, "DELETE FROM spiele WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $msg = "Match gelöscht!";
    $view = 'matches';
}

/* =========================
   Daten laden
========================= */
$teams_res = mysqli_query($con, "SELECT * FROM liga ORDER BY team ASC");

$matches_res = mysqli_query($con, "
    SELECT s.*,
           h.team AS heim_team, h.logo AS heim_logo,
           g.team AS gast_team, g.logo AS gast_logo
    FROM spiele s
    JOIN liga h ON h.id = s.heim_id
    JOIN liga g ON g.id = s.gast_id
    ORDER BY s.datum DESC, s.uhrzeit DESC
");
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .nav { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom: 12px; }
        .nav a { text-decoration:none; font-weight:bold; color:#0b6623; padding:6px 10px; border:1px solid #ddd; border-radius: 999px; }
        .nav a.active { background:#0b6623; color:white; border-color:#0b6623; }
        .msg { background:#eef; padding:10px; border-radius:8px; margin:10px 0; }
        .row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        input, select { padding:4px; }
        hr { margin: 12px 0; }
        .small { font-size:12px; color:#666; }
        .teamname { font-weight: bold; }
        .logoimg { height:18px; vertical-align:middle; }
        .btn { cursor:pointer; }
    </style>
</head>
<body>

<div class="nav">
    <a class="<?= $view==='teams'?'active':'' ?>" href="admin.php?view=teams">Teams</a>
    <a class="<?= $view==='matches'?'active':'' ?>" href="admin.php?view=matches">Matches</a>
    <a href="dashboard.php">Zurück</a>
</div>

<?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>


<?php if ($view === 'teams'): ?>

    <h1>Admin – Teams</h1>

    <h3>➕ Team hinzufügen</h3>
    <form method="post" class="row">
        Teamname:
        <input name="team" required placeholder="z.B. SV Pulkau">

        Logo (optional):
        <input name="logo" placeholder="z.B. pulkau.png">

        <button class="btn" name="add_team">Hinzufügen</button>
    </form>
    <div class="small">Logo-Datei liegt in <code>images/teams/</code> (wie bei dir in main.php).</div>

    <hr>

    <h3>✏ Teams bearbeiten</h3>

    <?php while ($t = mysqli_fetch_assoc($teams_res)): ?>
        <form method="post" class="row">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">

            <span class="teamname">
        <?php if (!empty($t['logo'])): ?>
            <img class="logoimg" src="images/teams/<?= htmlspecialchars($t['logo']) ?>" alt="">
        <?php endif; ?>
                <?= htmlspecialchars($t['team']) ?>
      </span>

            Punkte: <input name="punkte" value="<?= (int)$t['punkte'] ?>" size="3">
            Tore: <input name="tore" value="<?= (int)$t['tore'] ?>" size="3">
            Gegentore: <input name="gegentore" value="<?= (int)$t['gegentore'] ?>" size="3">

            <button class="btn" name="save_team">💾</button>
            <button class="btn" name="delete_team" onclick="return confirm('Team wirklich löschen? (nur möglich wenn keine Matches existieren)')">🗑</button>
        </form>
        <hr>
    <?php endwhile; ?>


<?php else: ?>

    <h1>Admin – Matches</h1>

    <h3>➕ Match hinzufügen</h3>
    <form method="post" class="row">
        Heim:
        <select name="heim_id" required>
            <?php mysqli_data_seek($teams_res, 0); while ($t = mysqli_fetch_assoc($teams_res)): ?>
                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['team']) ?></option>
            <?php endwhile; ?>
        </select>

        Gast:
        <select name="gast_id" required>
            <?php mysqli_data_seek($teams_res, 0); while ($t = mysqli_fetch_assoc($teams_res)): ?>
                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['team']) ?></option>
            <?php endwhile; ?>
        </select>

        Datum: <input type="date" name="datum" required>
        Zeit: <input type="time" name="uhrzeit" required>

        Heimtore: <input type="number" name="heim_tore" min="0" style="width:70px">
        Gasttore: <input type="number" name="gast_tore" min="0" style="width:70px">

        <button class="btn" name="add_match">➕</button>
    </form>

    <div class="small">Tore leer lassen = kommend. Tore eintragen = beendet.</div>

    <hr>

    <h3>✏ Matches bearbeiten</h3>

    <?php if ($matches_res && mysqli_num_rows($matches_res) > 0): ?>
        <?php while ($m = mysqli_fetch_assoc($matches_res)): ?>
            <form method="post" class="row">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">

                <span>
          <strong><?= htmlspecialchars($m['heim_team']) ?></strong>
          vs
          <strong><?= htmlspecialchars($m['gast_team']) ?></strong>
          (<?= htmlspecialchars($m['datum']) ?> <?= htmlspecialchars($m['uhrzeit']) ?>)
        </span>

                Heimtore:
                <input type="number" name="heim_tore" min="0" value="<?= $m['heim_tore'] ?? '' ?>" style="width:70px">

                Gasttore:
                <input type="number" name="gast_tore" min="0" value="<?= $m['gast_tore'] ?? '' ?>" style="width:70px">

                <button class="btn" name="save_match">💾</button>
                <button class="btn" name="delete_match" onclick="return confirm('Match wirklich löschen?')">🗑</button>
            </form>
            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Keine Matches vorhanden.</p>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>
