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

$name = isset($_POST["name"]) ? sanitizeFilename($_POST["name"]) : "";
$type = isset($_POST["type"]) ? $_POST["type"] : "";
$content = isset($_POST["content"]) ? $_POST["content"] : "";
$is_protected = isset($_POST["is_protected"]) && $_POST["is_protected"] === "1";
$password = isset($_POST["password"]) ? $_POST["password"] : null;

if (empty($name)) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "File name is required",
    ]);
    exit();
}

if (!isAllowedFileType($type)) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "Invalid file type",
    ]);
    exit();
}

if ($is_protected && empty($password)) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "error" => "Password is required for protected files",
    ]);
    exit();
}

$result = createNewFile(
    $user_id,
    $name,
    $type,
    $content,
    $is_protected,
    $password
);

if (!$result["success"]) {
    header("Content-Type: application/json");
    echo json_encode($result);
    exit();
}

$file = getFileById($result["file_id"], $user_id);

header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "message" => "File created successfully",
    "file" => [
        "id" => $file["id"],
        "name" => $file["name"],
        "type" => $file["type"],
        "is_protected" => (bool) $file["is_protected"],
        "created_at" => $file["created_at"],
        "updated_at" => $file["updated_at"],
    ],
]);
// Developer: DevZeus (Mahyar Asghari)