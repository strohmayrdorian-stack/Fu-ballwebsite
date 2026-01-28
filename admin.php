<?php
session_start();
if (!isset($_SESSION['login'])) die("Kein Zugriff");

$con = mysqli_connect("localhost", "root", "", "FBV");

if (isset($_POST['save'])) {
    $stmt = mysqli_prepare($con,
        "UPDATE liga SET punkte=?, tore=?, gegentore=? WHERE id=?"
    );
    mysqli_stmt_bind_param(
        $stmt,
        "iiii",
        $_POST['punkte'],
        $_POST['tore'],
        $_POST['gegentore'],
        $_POST['id']
    );
    mysqli_stmt_execute($stmt);
}

$res = mysqli_query($con, "SELECT * FROM liga");
?>

<h1>Admin – Teams bearbeiten</h1>

<?php while ($t = mysqli_fetch_assoc($res)): ?>
<form method="post">
    <input type="hidden" name="id" value="<?= $t['id'] ?>">
    <?= $t['team'] ?> |
    Punkte: <input name="punkte" value="<?= $t['punkte'] ?>" size="3">
    Tore: <input name="tore" value="<?= $t['tore'] ?>" size="3">
    Gegentore: <input name="gegentore" value="<?= $t['gegentore'] ?>" size="3">
    <button name="save">💾</button>
</form>
<hr>
<?php endwhile; ?>
