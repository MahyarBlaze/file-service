<?php
session_start();

if (
    !isset($_SESSION["user_id"]) &&
    basename($_SERVER["PHP_SELF"]) != "login.php" &&
    basename($_SERVER["PHP_SELF"]) != "register.php"
) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>ارائه دهنده فایل هوشمند</title>
      <link rel="stylesheet" href="assets/css/style.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
      <link href="https://fonts.googleapis.com/css2?family=Beiruti:wght@200..900&display=swap" rel="stylesheet">
   </head>
   <body>
      <div class="container">
         <header class="main-header">
            <div class="logo">
               <h1>ارائه دهنده فایل هوشمند</h1>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-menu">
               <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
               <a href="logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i>خروج </a>
            </div>
            <?php endif; ?>
         </header>
         <main class="dashboard">
            <div class="sidebar">
               <div class="sidebar-header">
                  <h2>فایل‌های من</h2>
                  <button id="newFileBtn" class="btn btn-primary"><i class="fas fa-plus"></i> فایل جدید</button>
               </div>
               <div class="search-box">
                  <input type="text" id="fileSearch" placeholder="جستجوی فایل...">
                  <i class="fas fa-search"></i>
               </div>
               <div class="file-list" id="fileList">
                  <div class="loading">در حال بارگذاری...</div>
               </div>
            </div>
            <div class="content-area">
               <div class="editor-header">
                  <h2 id="currentFileName">ویرایشگر فایل</h2>
                  <div class="editor-actions">
                     <button id="saveFileBtn" class="btn btn-success" disabled><i class="fas fa-save"></i>ذخیره </button>
                     <button id="deleteFileBtn" class="btn btn-danger" disabled><i class="fas fa-trash"></i>حذف </button>
                  </div>
               </div>
               <div class="editor-container">
                  <div id="editorPlaceholder" class="editor-placeholder">
                     <i class="fas fa-file-alt"></i>
                     <p>فایلی انتخاب نشده است</p>
                     <p class="hint">برای ویرایش، یک فایل را از لیست سمت راست انتخاب کنید یا یک فایل جدید ایجاد نمایید</p>
                  </div>
                  <textarea id="fileEditor" class="file-editor"></textarea>
               </div>
            </div>
         </main>
      </div>
      <div id="newFileModal" class="modal">
         <div class="modal-content">
            <div class="modal-header">
               <h2>ایجاد فایل جدید</h2>
               <span class="close">&times;</span>
            </div>
            <div class="modal-body">
               <div class="form-group">
                  <label for="newFileName">نام فایل:</label>
                  <input type="text" id="newFileName" placeholder="مثال: test">
               </div>
               <div class="form-group">
                  <label for="newFileType">نوع فایل:</label>
                  <select id="newFileType">
                     <option value="html">HTML</option>
                     <option value="css">CSS</option>
                     <option value="js">JavaScript</option>
                     <option value="json">JSON</option>
                     <option value="php">PHP</option>
                  </select>
               </div>
               <div class="form-group">
                  <label for="newFileContent">محتوای فایل:</label>
                  <textarea id="newFileContent" rows="10"></textarea>
               </div>
               <div class="form-group">
                  <label class="checkbox-label">
                  <input type="checkbox" id="isProtected"> محافظت با رمز عبور
                  </label>
               </div>
               <div class="form-group" id="passwordGroup" style="display: none;">
                  <label for="filePassword">رمز عبور فایل:</label>
                  <input type="password" id="filePassword">
               </div>
            </div>
            <div class="modal-footer">
               <button id="createFileBtn" class="btn btn-primary">ایجاد فایل</button>
            </div>
         </div>
      </div>
      <div id="passwordModal" class="modal">
         <div class="modal-content">
            <div class="modal-header">
               <h2>فایل محافظت شده</h2>
               <span class="close">&times;</span>
            </div>
            <div class="modal-body">
               <p>این فایل با رمز عبور محافظت شده است. لطفاً رمز عبور را وارد کنید.</p>
               <div class="form-group">
                  <label for="enterFilePassword">رمز عبور:</label>
                  <input type="password" id="enterFilePassword">
               </div>
            </div>
            <div class="modal-footer">
               <button id="submitPasswordBtn" class="btn btn-primary">تایید</button>
            </div>
         </div>
      </div>
      <div id="deleteModal" class="modal">
         <div class="modal-content">
            <div class="modal-header">
               <h2>حذف فایل</h2>
               <span class="close">&times;</span>
            </div>
            <div class="modal-body">
               <p>آیا از حذف این فایل اطمینان دارید؟</p>
               <p class="warning">این عملیات غیرقابل بازگشت است!</p>
               <div class="form-group" id="deletePasswordGroup" style="display: none;">
                  <label for="deleteFilePassword">رمز عبور فایل:</label>
                  <input type="password" id="deleteFilePassword">
               </div>
            </div>
            <div class="modal-footer">
               <button id="confirmDeleteBtn" class="btn btn-danger">حذف</button>
               <button class="btn btn-outline close-modal">انصراف</button>
            </div>
         </div>
      </div>
      <script src="assets/js/script.js"></script>
   </body>
</html>
<!-- Developer: DevZeus (Mahyar Asghari) -->