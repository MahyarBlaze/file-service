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

$file_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$password = isset($_GET["password"]) ? $_GET["password"] : null;

if ($file_id <= 0) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "Invalid file ID",
    ]);
    exit();
}

$file = getFileById($file_id, $user_id);

if (!$file) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "File not found",
    ]);
    exit();
}

if ($file["is_protected"]) {
    if (empty($password)) {
        header("Content-Type: application/json");
        echo json_encode([
            "success" => false,
            "error" => "File password required",
        ]);
        exit();
    }

    if (!password_verify($password, $file["password"])) {
        header("Content-Type: application/json");
        echo json_encode([
            "success" => false,
            "error" => "رمز عبور فایل نادرست است",
        ]);
        exit();
    }
}

header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "file" => [
        "id" => $file["id"],
        "name" => $file["name"],
        "type" => $file["type"],
        "content" => $file["content"],
        "is_protected" => (bool) $file["is_protected"],
        "created_at" => $file["created_at"],
        "updated_at" => $file["updated_at"],
    ],
]);
// Developer: DevZeus (Mahyar Asghari)