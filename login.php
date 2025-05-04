<?php
include "header.php";
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>
<body>
    <div class="everything">
        <div class="login-container">
            <h2>Kyçu</h2>
            <form method="POST" action="functions/login_process.php">
                <input type="email" name="email" placeholder="Email" autofocus="autofocus" required>
                <input type="password" name="password" placeholder="Fjalëkalimi" required>
                <button type="submit" name="submit">Kyçu</button>
            </form>
        </div>
    </div>
</body>
<?php include "footer.php"; ?>
</html>
