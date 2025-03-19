<?php
function getDbConnection()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die($conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    return $conn;
}

function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, "UTF-8");
}

function sanitizeFilename($filename)
{
    $filename = preg_replace("/[^\w\-\.]/", "", $filename);
    $filename = str_replace("..", "", $filename);
    return $filename;
}

function isAllowedFileType($type)
{
    return in_array(strtolower($type), ALLOWED_FILE_TYPES);
}

function getFileIcon($type)
{
    switch (strtolower($type)) {
        case "html":
            return '<i class="fas fa-file-code text-orange"></i>';
        case "css":
            return '<i class="fas fa-file-code text-blue"></i>';
        case "js":
            return '<i class="fas fa-file-code text-yellow"></i>';
        case "json":
            return '<i class="fas fa-file-code text-green"></i>';
        case "php":
            return '<i class="fas fa-file-code text-purple"></i>';
        default:
            return '<i class="fas fa-file"></i>';
    }
}

function generateToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

function getUserFiles($user_id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT id, name, type, is_protected, created_at, updated_at FROM files WHERE user_id = ? ORDER BY updated_at DESC"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $files = [];
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $files;
}

function getFileById($file_id, $user_id)
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT id, name, type, content, is_protected, password, created_at, updated_at FROM files WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    $file = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $file;
}

function createNewFile(
    $user_id,
    $name,
    $type,
    $content,
    $is_protected = false,
    $password = null
) {
    $conn = getDbConnection();

    $stmt = $conn->prepare(
        "SELECT id FROM files WHERE name = ? AND type = ? AND user_id = ?"
    );
    $stmt->bind_param("ssi", $name, $type, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return [
            "success" => false,
            "error" => "File already exists",
        ];
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

    $user_dir = FILES_DIR . "/$user_id";
    if (!file_exists($user_dir)) {
        mkdir($user_dir, 0755, true);
    }

    $file_path = "$user_dir/$name.$type";
    file_put_contents($file_path, $content);

    $stmt->close();
    $conn->close();

    return [
        "success" => true,
        "file_id" => $file_id,
    ];
}

function updateFile($file_id, $user_id, $content, $file_password = null)
{
    $conn = getDbConnection();

    $stmt = $conn->prepare(
        "SELECT id, name, type, is_protected, password FROM files WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return [
            "success" => false,
            "error" => "File not found",
        ];
    }

    $file = $result->fetch_assoc();

    if ($file["is_protected"]) {
        if (empty($file_password)) {
            $stmt->close();
            $conn->close();
            return [
                "success" => false,
                "error" => "File password required",
            ];
        }

        if (!password_verify($file_password, $file["password"])) {
            $stmt->close();
            $conn->close();
            return [
                "success" => false,
                "error" => "Invalid file password",
            ];
        }
    }

    $stmt = $conn->prepare(
        "UPDATE files SET content = ?, updated_at = NOW() WHERE id = ?"
    );
    $stmt->bind_param("si", $content, $file_id);
    $stmt->execute();

    $user_dir = FILES_DIR . "/$user_id";
    $file_path = "$user_dir/{$file["name"]}.{$file["type"]}";
    file_put_contents($file_path, $content);

    $stmt->close();
    $conn->close();

    return [
        "success" => true,
    ];
}

function deleteFile($file_id, $user_id, $file_password = null)
{
    $conn = getDbConnection();

    $stmt = $conn->prepare(
        "SELECT id, name, type, is_protected, password FROM files WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return [
            "success" => false,
            "error" => "File not found",
        ];
    }

    $file = $result->fetch_assoc();

    if ($file["is_protected"]) {
        if (empty($file_password)) {
            $stmt->close();
            $conn->close();
            return [
                "success" => false,
                "error" => "File password required",
            ];
        }

        if (!password_verify($file_password, $file["password"])) {
            $stmt->close();
            $conn->close();
            return [
                "success" => false,
                "error" => "Invalid file password",
            ];
        }
    }

    $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();

    $user_dir = FILES_DIR . "/$user_id";
    $file_path = "$user_dir/{$file["name"]}.{$file["type"]}";
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    $stmt->close();
    $conn->close();

    return [
        "success" => true,
    ];
}
// Developer: DevZeus (Mahyar Asghari)