<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "Authentication required",
    ]);
    exit();
}

require_once "../includes/config.php";
require_once "../includes/functions.php";

$user_id = $_SESSION["user_id"];

$files = getUserFiles($user_id);

header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "files" => $files,
]);
// Developer: DevZeus (Mahyar Asghari)