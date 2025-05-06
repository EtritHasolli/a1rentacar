<?php
include "header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>
<body>
    <div class="everything">
        <div class="login-container">
            <h2>Log In</h2>
            <form method="POST" action="functions/login_process.php">
                <input type="email" name="email" placeholder="Email" autofocus="autofocus" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="submit">Log In</button>
            </form>
        </div>
    </div>
</body>
<?php include "footer.php"; ?>
</html>
