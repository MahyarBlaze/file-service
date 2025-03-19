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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "Invalid request method",
    ]);
    exit();
}

$file_id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$content = isset($_POST["content"]) ? $_POST["content"] : "";
$password = isset($_POST["password"]) ? $_POST["password"] : null;

if ($file_id <= 0) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "Invalid file ID",
    ]);
    exit();
}

$result = updateFile($file_id, $user_id, $content, $password);

header("Content-Type: application/json");
echo json_encode($result);
// Developer: DevZeus (Mahyar Asghari)