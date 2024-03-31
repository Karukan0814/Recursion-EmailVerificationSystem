<?php

// phpでは、クッキーはsetcookie関数で設定します。
if (isset($_POST['theme'])) setcookie("theme", $_POST['theme'], time() + (86400 * 30), "/");

// HTTPリクエストに添付されたクッキーを取得するには、$_COOKIEスーパーグローバルを使用します。クッキーはキーと値のペアなので、連想配列として保存されます。
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : "default";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Theme Page Example</title>
        <style>
            body.default {
                background-color: white;
                color: black;
            }
            body.dark {
                background-color: #333;
                color: white;
            }
        </style>
    </head>
    <body class="<?php echo $theme; ?>">
        <h1>Theme Page Example</h1>
        <p>Select your preferred theme:</p>

        <form method="post">
            <select name="theme">
                <option value="default" <?= $theme == "default" ?  "selected" : '' ?>>Default Theme</option>
                <option value="dark" <?= $theme == "dark" ?  "selected" : '' ?>>Dark Theme</option>
            </select>
            <input type="submit" name="changeTheme" value="Change Theme">
        </form>
    </body>
</html>