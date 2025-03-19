<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: login.php");
exit();
// Developer: DevZeus (Mahyar Asghari)