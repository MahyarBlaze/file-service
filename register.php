<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "includes/config.php";
require_once "includes/functions.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        empty($username) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $error = "لطفاً تمام فیلدها را پر کنید.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "لطفاً یک ایمیل معتبر وارد کنید.";
    } elseif ($password !== $confirm_password) {
        $error = "رمز عبور و تکرار آن مطابقت ندارند.";
    } elseif (strlen($password) < 6) {
        $error = "رمز عبور باید حداقل 6 کاراکتر باشد.";
    } else {
        $conn = getDbConnection();

        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ?"
        );
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "نام کاربری یا ایمیل قبلاً ثبت شده است.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())"
            );
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $success =
                    "ثبت نام با موفقیت انجام شد. اکنون می‌توانید وارد شوید.";
            } else {
                $error = "خطا در ثبت نام. لطفاً دوباره تلاش کنید.";
            }
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
      <title>ثبت نام | ارائه دهنده فایل هوشمند</title>
      <link rel="stylesheet" href="assets/css/style.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
      <link href="https://fonts.googleapis.com/css2?family=Beiruti:wght@200..900&display=swap" rel="stylesheet">
   </head>
   <body class="auth-page">
      <div class="auth-container">
         <div class="auth-header">
            <h1>ارائه دهنده فایل هوشمند</h1>
            <p>برای استفاده از ارائه دهنده فایل هوشمند ثبت نام کنید</p>
         </div>
         <?php if (!empty($error)): ?>
         <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
         </div>
         <?php endif; ?>
         <?php if (!empty($success)): ?>
         <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <br>
            <a href="login.php">ورود به سیستم</a>
         </div>
         <?php endif; ?>
         <div class="auth-tabs">
            <a href="login.php" class="tab">ورود</a>
            <div class="tab active">ثبت نام</div>
         </div>
         <form class="auth-form" method="post" action="register.php">
            <div class="form-group">
               <label for="username">نام کاربری</label>
               <div class="input-icon">
                  <i class="fas fa-user"></i>
                  <input type="text" id="username" name="username" placeholder="یک نام کاربری انتخاب کنید" required>
               </div>
            </div>
            <div class="form-group">
               <label for="email">ایمیل</label>
               <div class="input-icon">
                  <i class="fas fa-envelope"></i>
                  <input type="email" id="email" name="email" placeholder="ایمیل خود را وارد کنید" required>
               </div>
            </div>
            <div class="form-group">
               <label for="password">رمز عبور</label>
               <div class="input-icon">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="password" name="password" placeholder="یک رمز عبور قوی انتخاب کنید" required>
               </div>
            </div>
            <div class="form-group">
               <label for="confirm_password">تکرار رمز عبور</label>
               <div class="input-icon">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="confirm_password" name="confirm_password" placeholder="رمز عبور را مجدداً وارد کنید" required>
               </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ثبت نام</button>
         </form>
         <div class="auth-footer">
            <p>قبلاً ثبت نام کرده‌اید؟ <a href="login.php">وارد شوید</a></p>
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