<?php
session_start();

/* Nicht eingeloggt */
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

/* Kein Admin */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="refresh" content="3;url=dashboard.php">
        <title>Zugriff verweigert</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f4f4f4;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .box {
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                text-align: center;
            }

        </style>
    </head>
    <body>
    <div class="box">
        <h2>⛔ Zugriff verweigert</h2>
        <p>Du wirst in 3 Sekunden zurück zum Dashboard weitergeleitet...</p>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$con = mysqli_connect("localhost", "root", "", "FBV");
if (!$con) die("Datenbankverbindung fehlgeschlagen");

$msg = "";

// Ansicht: teams | matches
$view = $_GET['view'] ?? 'teams';
if (!in_array($view, ['teams', 'matches'])) $view = 'teams';

/* =========================
   Upload-Helper
========================= */
function upload_team_logo($fieldName, $targetDir = "images/teams/")
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return array(false, "", "");
    }

    $f = $_FILES[$fieldName];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        return array(false, "", "Upload-Fehler (Code: ".$f['error'].")");
    }

    if ($f['size'] > 2 * 1024 * 1024) {
        return array(false, "", "Datei zu groß (max. 2MB)");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);

    $allowed = array(
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
    );

    if (!isset($allowed[$mime])) {
        return array(false, "", "Nur Bilder erlaubt (png, jpg, webp, gif)");
    }

    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }

    $ext = $allowed[$mime];
    $newName = "team_" . date("Ymd_His") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
    $destPath = rtrim($targetDir, "/") . "/" . $newName;

    if (!move_uploaded_file($f['tmp_name'], $destPath)) {
        return array(false, "", "Konnte Datei nicht speichern");
    }

    return array(true, $newName, "");
}

/* =========================
   TEAMS: hinzufügen
========================= */
if (isset($_POST['add_team'])) {
    $team = trim($_POST['team'] ?? '');
    $trainer = trim($_POST['trainer'] ?? '');
    $gruendung = trim($_POST['gruendung'] ?? '');
    $stadion = trim($_POST['stadion'] ?? '');
    $logo = "";

    if ($team === '') {
        $msg = "Teamname darf nicht leer sein!";
    } else {
        $result = upload_team_logo("logo_file");
        $ok = $result[0];
        $filename = $result[1];
        $err = $result[2];

        if ($err !== "") {
            $msg = $err;
        } else {
            if ($ok) $logo = $filename;

            $stmt = mysqli_prepare($con, "
                INSERT INTO liga (team, trainer, gruendung, stadion, logo)
                VALUES (?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmt, "sssss", $team, $trainer, $gruendung, $stadion, $logo);

            if (mysqli_stmt_execute($stmt)) {
                $msg = "Team hinzugefügt!";
            } else {
                $msg = "Fehler beim Hinzufügen: " . mysqli_error($con);
            }
        }
    }
}

/* =========================
   TEAMS: speichern
========================= */
if (isset($_POST['save_team'])) {
    $id = (int)$_POST['id'];
    $team = trim($_POST['team'] ?? '');
    $trainer = trim($_POST['trainer'] ?? '');
    $gruendung = trim($_POST['gruendung'] ?? '');
    $stadion = trim($_POST['stadion'] ?? '');
    $delete_logo = isset($_POST['delete_logo']) ? 1 : 0;

    $stmt0 = mysqli_prepare($con, "SELECT logo FROM liga WHERE id=?");
    mysqli_stmt_bind_param($stmt0, "i", $id);
    mysqli_stmt_execute($stmt0);
    $res0 = mysqli_stmt_get_result($stmt0);
    $row0 = mysqli_fetch_assoc($res0);
    $currentLogo = $row0['logo'] ?? "";

    $newLogo = $currentLogo;

    if ($delete_logo && $currentLogo !== "") {
        $path = "images/teams/" . $currentLogo;
        if (is_file($path)) @unlink($path);
        $newLogo = "";
    }

    $result = upload_team_logo("logo_file_edit");
    $ok = $result[0];
    $filename = $result[1];
    $err = $result[2];

    if ($err !== "") {
        $msg = $err;
    } else {
        if ($ok) {
            if ($currentLogo !== "") {
                $oldPath = "images/teams/" . $currentLogo;
                if (is_file($oldPath)) @unlink($oldPath);
            }
            $newLogo = $filename;
        }

        $stmt = mysqli_prepare($con, "
            UPDATE liga
            SET team=?, trainer=?, gruendung=?, stadion=?, logo=?
            WHERE id=?
        ");
        mysqli_stmt_bind_param($stmt, "sssssi", $team, $trainer, $gruendung, $stadion, $newLogo, $id);
        mysqli_stmt_execute($stmt);

        $msg = "Team gespeichert!";
    }
}

/* =========================
   TEAMS: löschen
========================= */
if (isset($_POST['delete_team'])) {
    $id = (int)$_POST['id'];

    $stmt0 = mysqli_prepare($con, "SELECT logo FROM liga WHERE id=?");
    mysqli_stmt_bind_param($stmt0, "i", $id);
    mysqli_stmt_execute($stmt0);
    $res0 = mysqli_stmt_get_result($stmt0);
    $row0 = mysqli_fetch_assoc($res0);
    $logo = $row0['logo'] ?? "";

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

        if ($logo !== "") {
            $path = "images/teams/" . $logo;
            if (is_file($path)) @unlink($path);
        }

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
   MATCHES: speichern
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
        input, select { padding:6px; }
        hr { margin: 12px 0; }
        .small { font-size:12px; color:#666; }
        .teamname { font-weight: bold; min-width: 140px; }
        .logoimg { height:22px; vertical-align:middle; border-radius:4px; }
        .btn { cursor:pointer; padding:6px 10px; }
        .file { max-width: 220px; }
    </style>
</head>
<body>

<div class="nav">
    <a class="<?= $view==='teams'?'active':'' ?>" href="admin.php?view=teams">Teams</a>
    <a class="<?= $view==='matches'?'active':'' ?>" href="admin.php?view=matches">Matches</a>
    <a href="dashboard.php" class="btn-back">← Zurück</a>
</div>

<?php if ($msg): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($view === 'teams'): ?>

    <h1>Admin – Teams</h1>

    <h3>➕ Team hinzufügen</h3>
    <form method="post" class="row" enctype="multipart/form-data">
        <input name="team" required placeholder="Teamname">
        <input name="trainer" placeholder="Trainer">
        <input name="gruendung" placeholder="Gegründet">
        <input name="stadion" placeholder="Stadion">
        <input class="file" type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/gif">
        <button class="btn" name="add_team">Hinzufügen</button>
    </form>

    <div class="small">Statistiken werden automatisch aus den Matches berechnet.</div>

    <hr>

    <h3>✏ Teams bearbeiten</h3>

    <?php while ($t = mysqli_fetch_assoc($teams_res)): ?>
        <form method="post" class="row" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">

            <span class="teamname">
                <?php if (!empty($t['logo'])): ?>
                    <img class="logoimg" src="images/teams/<?= htmlspecialchars($t['logo']) ?>" alt="">
                <?php endif; ?>
                <?= htmlspecialchars($t['team']) ?>
            </span>

            <input name="team" value="<?= htmlspecialchars($t['team']) ?>" placeholder="Teamname">
            <input name="trainer" value="<?= htmlspecialchars($t['trainer'] ?? '') ?>" placeholder="Trainer">
            <input name="gruendung" value="<?= htmlspecialchars($t['gruendung'] ?? '') ?>" placeholder="Gegründet">
            <input name="stadion" value="<?= htmlspecialchars($t['stadion'] ?? '') ?>" placeholder="Stadion">

            <input class="file" type="file" name="logo_file_edit" accept="image/png,image/jpeg,image/webp,image/gif">

            <?php if (!empty($t['logo'])): ?>
                <label class="small">
                    <input type="checkbox" name="delete_logo"> Logo löschen
                </label>
            <?php endif; ?>

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
<footer style="margin-top:30px; padding-top:15px; border-top:1px solid #ddd; text-align:center; color:#666; font-size:14px;">
    © <?= date("Y") ?> Fußballverwaltung - Fasching - Strohmayr
</footer>
</body>
</html>