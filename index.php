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
        <form action="darkchat.php" method="GET" class="login-form">
            <label>CODENAME:</label>
            <input type="text" name="username" required autocomplete="off" placeholder="GHOST_USER">
            <label>ENCRYPTION KEY:</label>
            <input type="password" required placeholder="********">
            <input type="submit" value="PROCEED" class="send-btn">
        </form>
    </div>
</body>
</html>