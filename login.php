<?php

session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "includes/config.php";
require_once "includes/functions.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "لطفاً نام کاربری و رمز عبور را وارد کنید.";
    } else {
        $conn = getDbConnection();

        $stmt = $conn->prepare(
            "SELECT id, username, password FROM users WHERE username = ?"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {
                session_regenerate_id();
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];

                header("Location: index.php");
                exit();
            } else {
                $error = "نام کاربری یا رمز عبور اشتباه است.";
            }
        } else {
            $error = "نام کاربری یا رمز عبور اشتباه است.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>ورود | ارائه دهنده فایل هوشمند</title>
      <link rel="stylesheet" href="assets/css/style.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
      <link href="https://fonts.googleapis.com/css2?family=Beiruti:wght@200..900&display=swap" rel="stylesheet">
   </head>
   <body class="auth-page">
      <div class="auth-container">
         <div class="auth-header">
            <h1>ارائه دهنده فایل هوشمند</h1>
            <p>برای دسترسی به فایل‌های خود وارد شوید</p>
         </div>
         <?php if (!empty($error)): ?>
         <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
         </div>
         <?php endif; ?>
         <div class="auth-tabs">
            <div class="tab active">ورود</div>
            <a href="register.php" class="tab">ثبت نام</a>
         </div>
         <form class="auth-form" method="post" action="login.php">
            <div class="form-group">
               <label for="username">نام کاربری</label>
               <div class="input-icon">
                  <i class="fas fa-user"></i>
                  <input type="text" id="username" name="username" placeholder="نام کاربری خود را وارد کنید" required>
               </div>
            </div>
            <div class="form-group">
               <label for="password">رمز عبور</label>
               <div class="input-icon">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="password" name="password" placeholder="رمز عبور خود را وارد کنید" required>
               </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ورود</button>
         </form>
         <div class="auth-footer">
            <p>حساب کاربری ندارید؟ <a href="register.php">ثبت نام کنید</a></p>
         </div>
      </div>
      <div class="features">
         <h2>ویژگی‌های ارائه دهنده</h2>
         <div class="feature-grid">
            <div class="feature-card">
               <i class="fas fa-file-code"></i>
               <h3>مدیریت فایل‌ها</h3>
               <p>ایجاد، ویرایش، مشاهده و حذف انواع فایل‌های HTML، CSS، JS، JSON و PHP</p>
            </div>
            <div class="feature-card">
               <i class="fas fa-shield-alt"></i>
               <h3>امنیت بالا</h3>
               <p>محافظت از فایل‌ها با رمز عبور و سیستم احراز هویت کاربران</p>
            </div>
            <div class="feature-card">
               <i class="fas fa-code"></i>
               <h3>API اختصاصی</h3>
               <p>دسترسی به فایل‌ها از طریق API برای استفاده در سایر سیستم‌ها</p>
            </div>
         </div>
      </div>
   </body>
</html>
<!-- Developer: DevZeus (Mahyar Asghari) -->