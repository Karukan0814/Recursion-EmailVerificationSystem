<!DOCTYPE html>
<html>
    <head>
        <title>User Info Verification Mail</title>
    </head>
    <body>
    <h1>User Info Verification Mail</h1>
    <p>Please click a link below to verify your account.</p>
    <a href="<?= htmlspecialchars($verificationLink) ?>"><?= htmlspecialchars($verificationLink)?></a>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> User Info Verification Mail</p>
    </footer>
    </body>
</html>