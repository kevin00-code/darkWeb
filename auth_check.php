<?php
session_start();

// Redirect to login if the codename is missing
function verify_access() {
    if (!isset($_SESSION['codename'])) {
        header("Location: login.php");
        exit();
    }
}
?>
