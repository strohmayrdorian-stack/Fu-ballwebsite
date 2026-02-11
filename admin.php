<?php
session_start();
if (!isset($_SESSION['login'])) die("Kein Zugriff");

$con = mysqli_connect("localhost", "root", "", "FBV");
if (!$con) die("DB Verbindung fehlgeschlagen");

// Welche Ansicht? teams | matches
$view = $_GET['view'] ?? 'teams';
if (!in_array($view, ['teams','matches'])) $view = 'teams';

/* =======================
   TEAMS speichern
======================= */
if (isset($_POST['save_team'])) {
    $stmt = mysqli_prepare($con, "UPDATE liga SET punkte=?, tore=?, gegentore=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "iiii", $_POST['punkte'], $_POST['tore'], $_POST['gegentore'], $_POST['id']);
    mysqli_stmt_execute($stmt);
}

/* =======================
   MATCH hinzufügen
======================= */
if (isset($_POST['add_match'])) {
    $heim_id = (int)$_POST['heim_id'];
    $gast_id = (int)$_POST['gast_id'];
    $datum = $_POST['datum'];
    $uhrzeit = $_POST['uhrzeit'];

    // Tore optional (leer => NULL)
    $heim_tore = ($_POST['heim_tore'] === '') ? null : (int)$_POST['heim_tore'];
    $gast_tore = ($_POST['gast_tore'] === '') ? null : (int)$_POST['gast_tore'];

    if ($heim_id === $gast_id) {
        $msg = "Heim und Gast dürfen nicht gleich sein!";
    } else {
        $stmt = mysqli_prepare($con, "
            INSERT INTO spiele (heim_id, gast_id, datum, uhrzeit, heim_tore, gast_tore)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        // i i s s i i (aber NULL braucht trick -> bind als int + set null geht mit mysqli ok, wenn variable null ist)
        mysqli_stmt_bind_param($stmt, "iissii", $heim_id, $gast_id, $datum, $uhrzeit, $heim_tore, $gast_tore);
        mysqli_stmt_execute($stmt);
        $msg = "Match hinzugefügt!";
    }
}

/* =======================
   MATCH updaten (Ergebnis ändern)
======================= */
if (isset($_POST['save_match'])) {
    $id = (int)$_POST['id'];

    $heim_tore = ($_POST['heim_tore'] === '') ? null : (int)$_POST['heim_tore'];
    $gast_tore = ($_POST['gast_tore'] === '') ? null : (int)$_POST['gast_tore'];

    $stmt = mysqli_prepare($con, "UPDATE spiele SET heim_tore=?, gast_tore=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "iii", $heim_tore, $gast_tore, $id);
    mysqli_stmt_execute($stmt);
    $msg = "Match aktualisiert!";
}

/* =======================
   MATCH löschen (optional)
======================= */
if (isset($_POST['delete_match'])) {
    $id = (int)$_POST['id'];
    $stmt = mysqli_prepare($con, "DELETE FROM spiele WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $msg = "Match gelöscht!";
}

// Daten laden
$teams_res = mysqli_query($con, "SELECT * FROM liga ORDER BY team ASC");

$matches_res = mysqli_query($con, "
    SELECT s.*, 
           h.team AS heim_team, g.team AS gast_team
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
        .nav a { margin-right: 10px; font-weight: bold; text-decoration: none; }
        .nav a.active { text-decoration: underline; }
        hr { margin: 12px 0; }
        input, select { padding: 3px; }
        .msg { padding: 8px; background: #eef; margin: 10px 0; }
        .small { font-size: 12px; color: #666; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    </style>
</head>
<body>

<div class="nav">
    <a class="<?= $view==='teams'?'active':'' ?>" href="admin.php?view=teams">Teams</a>
    <a class="<?= $view==='matches'?'active':'' ?>" href="admin.php?view=matches">Matches</a>
    <a href="main.php">Zurück</a>
</div>

<?php if (!empty($msg)): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($view === 'teams'): ?>

    <h1>Admin – Teams bearbeiten</h1>

    <?php mysqli_data_seek($teams_res, 0); ?>
    <?php while ($t = mysqli_fetch_assoc($teams_res)): ?>
        <form method="post" class="row">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <strong><?= htmlspecialchars($t['team']) ?></strong>
            Punkte: <input name="punkte" value="<?= (int)$t['punkte'] ?>" size="3">
            Tore: <input name="tore" value="<?= (int)$t['tore'] ?>" size="3">
            Gegentore: <input name="gegentore" value="<?= (int)$t['gegentore'] ?>" size="3">
            <button name="save_team">💾</button>
        </form>
        <hr>
    <?php endwhile; ?>

<?php else: ?>

    <h1>Admin – Matches verwalten</h1>

    <h3>Match hinzufügen</h3>
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

        <button name="add_match">➕</button>
    </form>

    <p class="small">Tore leer lassen = kommendes Match. Tore eintragen = beendet.</p>

    <h3>Matches</h3>

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

                <button name="save_match">💾</button>
                <button name="delete_match" onclick="return confirm('Match wirklich löschen?')">🗑</button>
            </form>
            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Keine Matches vorhanden.</p>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>
