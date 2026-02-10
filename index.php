<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username'])) {
    $_SESSION['codename'] = htmlspecialchars($_POST['username']);
    header("Location: darkchat.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DARKWEB // LOGIN</title>
    <link rel="stylesheet" href="chatbox.css">
</head>
<body>
    <div class="chat-container login-box">
        <h1 id="welcome">SYSTEM ACCESS</h1>
        <form action="index.php" method="POST" class="login-form">
            <label>CODENAME:</label>
            <input type="text" name="username" required autocomplete="off" placeholder="DONT SHARE">
            
            <label>ENCRYPTION KEY:</label>
            <input type="password" required placeholder="..KEY..">
            
            <input type="submit" value="PROCEED" class="send-btn">
        </form>
    </div>
</body>
</html>

