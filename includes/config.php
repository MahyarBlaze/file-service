<?php
define("DB_HOST", "localhost");
define("DB_USER", "");
define("DB_PASS", '');
define("DB_NAME", "");

define("FILES_DIR", __DIR__ . "/../files");

define("ALLOWED_FILE_TYPES", ["html", "css", "js", "json", "php"]);
define("MAX_FILE_SIZE", 5 * 1024 * 1024); // 5 مگابایت در صورت نیاز افزایش بدید

if (!file_exists(FILES_DIR)) {
    mkdir(FILES_DIR, 0755, true);
}
// Developer: DevZeus (Mahyar Asghari)