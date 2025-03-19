<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once "../includes/config.php";
require_once "../includes/functions.php";

$request_uri = $_SERVER["REQUEST_URI"];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = preg_replace("/^\/api\//", "", $path);
$segments = explode("/", $path);
$resource = $segments[0] ?? "";

$method = $_SERVER["REQUEST_METHOD"];

$data = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = [];
}

function authenticate()
{
    $headers = getallheaders();
    $auth_header = $headers["Authorization"] ?? "";

    if (
        empty($auth_header) ||
        !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)
    ) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Authentication required",
        ]);
        exit();
    }

    $token = $matches[1];

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT user_id FROM api_tokens WHERE token = ? AND expires_at > NOW()"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Invalid or expired token",
        ]);
        exit();
    }

    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    return $row["user_id"];
}

switch ($resource) {
    case "auth":
        handleAuth($method, $data);
        break;
    case "files":
        handleFiles($method, $data, $segments);
        break;
    default:
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "Resource not found",
        ]);
}

function handleAuth($method, $data)
{
    if ($method !== "POST") {
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "error" => "Method not allowed",
        ]);
        return;
    }

    $action = $data["action"] ?? "";

    switch ($action) {
        case "login":
            apiLogin($data);
            break;
        case "register":
            apiRegister($data);
            break;
        default:
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error" => "Invalid action",
            ]);
    }
}

function apiLogin($data)
{
    $username = $data["username"] ?? "";
    $password = $data["password"] ?? "";

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Username and password are required",
        ]);
        return;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT id, username, email, password FROM users WHERE username = ?"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Invalid credentials",
        ]);
        $stmt->close();
        $conn->close();
        return;
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user["password"])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "error" => "Invalid credentials",
        ]);
        $stmt->close();
        $conn->close();
        return;
    }

    $token = bin2hex(random_bytes(32));
    $expires_at = date("Y-m-d H:i:s", strtotime("+30 days"));

    $stmt = $conn->prepare(
        "INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("iss", $user["id"], $token, $expires_at);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user" => [
            "id" => $user["id"],
            "username" => $user["username"],
            "email" => $user["email"],
        ],
        "token" => $token,
        "expires_at" => $expires_at,
    ]);
}

function apiRegister($data)
{
    $username = $data["username"] ?? "";
    $email = $data["email"] ?? "";
    $password = $data["password"] ?? "";

    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Username, email, and password are required",
        ]);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid email format",
        ]);
        return;
    }

    $conn = getDbConnection();

    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE username = ? OR email = ?"
    );
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Username or email already exists",
        ]);
        $stmt->close();
        $conn->close();
        return;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())"
    );
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    $stmt->execute();

    $user_id = $conn->insert_id;

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => true,
        "message" => "Registration successful",
        "user" => [
            "id" => $user_id,
            "username" => $username,
            "email" => $email,
        ],
    ]);
}

function handleFiles($method, $data, $segments)
{
    $user_id = authenticate();

    switch ($method) {
        case "GET":
            if (isset($segments[1])) {
                getFile($user_id, $segments[1]);
            } else {
                getFiles($user_id);
            }
            break;
        case "POST":
            createFile($user_id, $data);
            break;
        case "PUT":
            if (!isset($segments[1])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "File ID is required",
                ]);
                return;
            }
            updateFile($user_id, $segments[1], $data);
            break;
        case "DELETE":
            if (!isset($segments[1])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "error" => "File ID is required",
                ]);
                return;
            }
            deleteFile($user_id, $segments[1]);
            break;
        default:
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "error" => "Method not allowed",
            ]);
    }
}

function getFiles($user_id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT id, name, type, is_protected, created_at, updated_at FROM files WHERE user_id = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[] = [
            "id" => $row["id"],
            "name" => $row["name"],
            "type" => $row["type"],
            "is_protected" => (bool) $row["is_protected"],
            "created_at" => $row["created_at"],
            "updated_at" => $row["updated_at"],
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => true,
        "files" => $files,
    ]);
}

function getFile($user_id, $file_id)
{
    $file_password = $_GET["password"] ?? null;

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT id, name, type, content, is_protected, password, created_at, updated_at FROM files WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "File not found",
        ]);
        $stmt->close();
        $conn->close();
        return;
    }

    $file = $result->fetch_assoc();

    if ($file["is_protected"]) {
        if (empty($file_password)) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "File password required",
                "file" => [
                    "id" => $file["id"],
                    "name" => $file["name"],
                    "type" => $file["type"],
                    "is_protected" => (bool) $file["is_protected"],
                    "created_at" => $file["created_at"],
                    "updated_at" => $file["updated_at"],
                ],
            ]);
            $stmt->close();
            $conn->close();
            return;
        }

        if (!password_verify($file_password, $file["password"])) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "Invalid file password",
            ]);
            $stmt->close();
            $conn->close();
            return;
        }
    }

    $stmt->close();
    $conn->close();

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
}

function createFile($user_id, $data)
{
    $name = $data["name"] ?? "";
    $type = $data["type"] ?? "";
    $content = $data["content"] ?? "";
    $is_protected = isset($data["is_protected"])
        ? (bool) $data["is_protected"]
        : false;
    $password = $data["password"] ?? null;

    if (empty($name) || empty($type)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "File name and type are required",
        ]);
        return;
    }

    $allowed_types = ["html", "css", "js", "json", "php"];
    if (!in_array($type, $allowed_types)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" =>
                "Invalid file type. Allowed types: html, css, js, json, php",
        ]);
        return;
    }

    $name = preg_replace("/[^\w\-\.]/", "", $name);

    $conn = getDbConnection();

    $stmt = $conn->prepare(
        "SELECT id FROM files WHERE name = ? AND type = ? AND user_id = ?"
    );
    $stmt->bind_param("ssi", $name, $type, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "File already exists",
        ]);
        $stmt->close();
        $conn->close();
        return;
    }

    $hashed_password = null;
    if ($is_protected && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }

    $stmt = $conn->prepare(
        "INSERT INTO files (user_id, name, type, content, is_protected, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $stmt->bind_param(
        "isssss",
        $user_id,
        $name,
        $type,
        $content,
        $is_protected,
        $hashed_password
    );
    $stmt->execute();

    $file_id = $conn->insert_id;

    $user_dir = "../files/$user_id";
    if (!file_exists($user_dir)) {
        mkdir($user_dir, 0755, true);
    }

    $file_path = "$user_dir/$name.$type";
    file_put_contents($file_path, $content);

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => true,
        "message" => "File created successfully",
        "file" => [
            "id" => $file_id,
            "name" => $name,
            "type" => $type,
            "is_protected" => $is_protected,
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
        ],
    ]);
}

function updateFile($user_id, $file_id, $data)
{
    $content = $data["content"] ?? "";
    $file_password = $data["password"] ?? null;

    $conn = getDbConnection();

    $stmt = $conn->prepare(
        "SELECT id, name, type, is_protected, password FROM files WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "File not found",
        ]);
        $stmt->close();
        $conn->close();
        return;
    }

    $file = $result->fetch_assoc();

    if ($file["is_protected"]) {
        if (empty($file_password)) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "File password required",
            ]);
            $stmt->close();
            $conn->close();
            return;
        }

        if (!password_verify($file_password, $file["password"])) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "Invalid file password",
            ]);
            $stmt->close();
            $conn->close();
            return;
        }
    }

    $stmt = $conn->prepare(
        "UPDATE files SET content = ?, updated_at = NOW() WHERE id = ?"
    );
    $stmt->bind_param("si", $content, $file_id);
    $stmt->execute();

    $user_dir = "../files/$user_id";
    $file_path = "$user_dir/{$file["name"]}.{$file["type"]}";
    file_put_contents($file_path, $content);

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => true,
        "message" => "File updated successfully",
        "file" => [
            "id" => $file["id"],
            "name" => $file["name"],
            "type" => $file["type"],
            "is_protected" => (bool) $file["is_protected"],
            "updated_at" => date("Y-m-d H:i:s"),
        ],
    ]);
}

function deleteFile($user_id, $file_id)
{
    $file_password = $_GET["password"] ?? null;

    $conn = getDbConnection();

    $stmt = $conn->prepare(
        "SELECT id, name, type, is_protected, password FROM files WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "File not found",
        ]);
        $stmt->close();
        $conn->close();
        return;
    }

    $file = $result->fetch_assoc();

    if ($file["is_protected"]) {
        if (empty($file_password)) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "File password required",
            ]);
            $stmt->close();
            $conn->close();
            return;
        }

        if (!password_verify($file_password, $file["password"])) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "error" => "Invalid file password",
            ]);
            $stmt->close();
            $conn->close();
            return;
        }
    }

    $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();

    $user_dir = "../files/$user_id";
    $file_path = "$user_dir/{$file["name"]}.{$file["type"]}";
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        "success" => true,
        "message" => "File deleted successfully",
    ]);
}
// Developer: DevZeus (Mahyar Asghari)