const fileList = document.getElementById('fileList');
const fileEditor = document.getElementById('fileEditor');
const editorPlaceholder = document.getElementById('editorPlaceholder');
const currentFileName = document.getElementById('currentFileName');
const saveFileBtn = document.getElementById('saveFileBtn');
const deleteFileBtn = document.getElementById('deleteFileBtn');
const newFileBtn = document.getElementById('newFileBtn');
const fileSearch = document.getElementById('fileSearch');

const newFileModal = document.getElementById('newFileModal');
const passwordModal = document.getElementById('passwordModal');
const deleteModal = document.getElementById('deleteModal');
const newFileName = document.getElementById('newFileName');
const newFileType = document.getElementById('newFileType');
const newFileContent = document.getElementById('newFileContent');
const isProtected = document.getElementById('isProtected');
const passwordGroup = document.getElementById('passwordGroup');
const filePassword = document.getElementById('filePassword');
const enterFilePassword = document.getElementById('enterFilePassword');
const deleteFilePassword = document.getElementById('deleteFilePassword');
const deletePasswordGroup = document.getElementById('deletePasswordGroup');
const createFileBtn = document.getElementById('createFileBtn');
const submitPasswordBtn = document.getElementById('submitPasswordBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

let currentFile = null;
let files = [];
let isEditing = false;

document.addEventListener('DOMContentLoaded', () => {
   loadFiles();
   setupEventListeners();
});

function loadFiles() {
   fetch('api/files.php')
      .then(response => response.json())
      .then(data => {
         if (data.success) {
            files = data.files;
            renderFiles(files);
         } else {
            showError(data.error || 'خطا در بارگذاری فایل‌ها');
         }
      })
      .catch(error => {
         console.error('Error loading files:', error);
         showError('خطا در ارتباط با سرور');
      });
}

function renderFiles(filesToRender) {
   fileList.innerHTML = '';

   if (filesToRender.length === 0) {
      fileList.innerHTML = '<div class="text-center">هیچ فایلی یافت نشد</div>';
      return;
   }

   filesToRender.forEach(file => {
      const fileItem = document.createElement('div');
      fileItem.className = `file-item ${currentFile && currentFile.id === file.id ? 'active' : ''}`;
      fileItem.dataset.id = file.id;

      let fileTypeIcon;
      switch (file.type) {
         case 'html':
            fileTypeIcon = '<i class="fas fa-file-code text-orange"></i>';
            break;
         case 'css':
            fileTypeIcon = '<i class="fas fa-file-code text-blue"></i>';
            break;
         case 'js':
            fileTypeIcon = '<i class="fas fa-file-code text-yellow"></i>';
            break;
         case 'json':
            fileTypeIcon = '<i class="fas fa-file-code text-green"></i>';
            break;
         case 'php':
            fileTypeIcon = '<i class="fas fa-file-code text-purple"></i>';
            break;
         default:
            fileTypeIcon = '<i class="fas fa-file"></i>';
      }

      fileItem.innerHTML = `
            <div class="file-icon">${fileTypeIcon}</div>
            <div class="file-info">
                <div class="file-name">${file.name}.${file.type}</div>
                <div class="file-meta">
                    آخرین ویرایش: ${formatDate(file.updated_at)}
                    ${file.is_protected ? '<i class="fas fa-lock file-protected" title="محافظت شده با رمز عبور"></i>' : ''}
                </div>
            </div>
        `;

      fileItem.addEventListener('click', () => selectFile(file));
      fileList.appendChild(fileItem);
   });
}

function selectFile(file) {
   if (isEditing && currentFile && currentFile.id !== file.id) {
      if (!confirm('تغییرات ذخیره نشده‌ای دارید. آیا مطمئن هستید که می‌خواهید فایل را تغییر دهید؟')) {
         return;
      }
   }

   if (file.is_protected) {
      currentFile = file;
      enterFilePassword.value = '';
      passwordModal.style.display = 'block';
      return;
   }

   loadFileContent(file.id);
}


function loadFileContent(fileId, password = null) {
   const url = password ?
      `api/file.php?id=${fileId}&password=${encodeURIComponent(password)}` :
      `api/file.php?id=${fileId}`;

   fetch(url)
      .then(response => response.json())
      .then(data => {
         if (data.success) {
            displayFile(data.file);
            passwordModal.style.display = 'none';
         } else {
            showError(data.error || 'خطا در بارگذاری محتوای فایل');
            if (data.error === 'رمز عبور فایل نادرست است') {
               enterFilePassword.value = '';
               enterFilePassword.focus();
            }
         }
      })
      .catch(error => {
         console.error('Error loading file content:', error);
         showError('خطا در ارتباط با سرور');
      });
}

function displayFile(file) {
   currentFile = file;
   fileEditor.value = file.content;
   currentFileName.textContent = `${file.name}.${file.type}`;

   editorPlaceholder.style.display = 'none';
   fileEditor.style.display = 'block';

   saveFileBtn.disabled = false;
   deleteFileBtn.disabled = false;

   const fileItems = document.querySelectorAll('.file-item');
   fileItems.forEach(item => {
      if (parseInt(item.dataset.id) === file.id) {
         item.classList.add('active');
      } else {
         item.classList.remove('active');
      }
   });

   isEditing = false;
}

function saveFile() {
   if (!currentFile) return;

   const content = fileEditor.value;
   const formData = new FormData();
   formData.append('id', currentFile.id);
   formData.append('content', content);

   if (currentFile.is_protected) {
      const password = prompt('این فایل با رمز عبور محافظت شده است. لطفاً رمز عبور را وارد کنید:');
      if (password === null) return;
      formData.append('password', password);
   }

   fetch('api/save_file.php', {
         method: 'POST',
         body: formData
      })
      .then(response => response.json())
      .then(data => {
         if (data.success) {
            showSuccess('فایل با موفقیت ذخیره شد');
            isEditing = false;

            const fileIndex = files.findIndex(f => f.id === currentFile.id);
            if (fileIndex !== -1) {
               files[fileIndex].updated_at = new Date().toISOString();
               renderFiles(files);
            }
         } else {
            showError(data.error || 'خطا در ذخیره فایل');
         }
      })
      .catch(error => {
         console.error('Error saving file:', error);
         showError('خطا در ارتباط با سرور');
      });
}

function deleteFile() {
   if (!currentFile) return;

   deleteModal.style.display = 'block';

   if (currentFile.is_protected) {
      deletePasswordGroup.style.display = 'block';
      deleteFilePassword.value = '';
   } else {
      deletePasswordGroup.style.display = 'none';
   }
}

function confirmDelete() {
   if (!currentFile) return;

   const formData = new FormData();
   formData.append('id', currentFile.id);

   if (currentFile.is_protected) {
      const password = deleteFilePassword.value;
      if (!password) {
         showError('لطفاً رمز عبور فایل را وارد کنید');
         return;
      }
      formData.append('password', password);
   }

   fetch('api/delete_file.php', {
         method: 'POST',
         body: formData
      })
      .then(response => response.json())
      .then(data => {
         if (data.success) {
            showSuccess('فایل با موفقیت حذف شد');

            files = files.filter(f => f.id !== currentFile.id);
            renderFiles(files);

            resetEditor();

            deleteModal.style.display = 'none';
         } else {
            showError(data.error || 'خطا در حذف فایل');
         }
      })
      .catch(error => {
         console.error('Error deleting file:', error);
         showError('خطا در ارتباط با سرور');
      });
}

function createFile() {
   const name = newFileName.value.trim();
   const type = newFileType.value;
   const content = newFileContent.value;
   const protected = isProtected.checked;
   const password = protected ? filePassword.value : '';

   if (!name) {
      showError('لطفاً نام فایل را وارد کنید');
      return;
   }

   if (!/^[a-zA-Z0-9\-_]+$/.test(name)) {
      showError('نام فایل فقط می‌تواند شامل حروف انگلیسی، اعداد، خط تیره و زیرخط باشد');
      return;
   }

   if (protected && !password) {
      showError('لطفاً رمز عبور فایل را وارد کنید');
      return;
   }

   const formData = new FormData();
   formData.append('name', name);
   formData.append('type', type);
   formData.append('content', content);
   formData.append('is_protected', protected ? '1' : '0');
   if (protected) {
      formData.append('password', password);
   }

   fetch('api/create_file.php', {
         method: 'POST',
         body: formData
      })
      .then(response => response.json())
      .then(data => {
         if (data.success) {
            showSuccess('فایل با موفقیت ایجاد شد');

            files.unshift(data.file);
            renderFiles(files);

            selectFile(data.file);

            newFileModal.style.display = 'none';
            resetNewFileForm();
         } else {
            showError(data.error || 'خطا در ایجاد فایل');
         }
      })
      .catch(error => {
         console.error('Error creating file:', error);
         showError('خطا در ارتباط با سرور');
      });
}

function resetEditor() {
   currentFile = null;
   fileEditor.value = '';
   currentFileName.textContent = 'ویرایشگر فایل';

   editorPlaceholder.style.display = 'flex';
   fileEditor.style.display = 'none';

   saveFileBtn.disabled = true;
   deleteFileBtn.disabled = true;

   isEditing = false;
}

function resetNewFileForm() {
   newFileName.value = '';
   newFileType.value = 'html';
   newFileContent.value = '';
   isProtected.checked = false;
   passwordGroup.style.display = 'none';
   filePassword.value = '';
}

function searchFiles(query) {
   if (!query) {
      renderFiles(files);
      return;
   }

   const filteredFiles = files.filter(file =>
      file.name.toLowerCase().includes(query.toLowerCase()) ||
      file.type.toLowerCase().includes(query.toLowerCase())
   );

   renderFiles(filteredFiles);
}

function setupEventListeners() {
   fileEditor.addEventListener('input', () => {
      isEditing = true;
   });

   saveFileBtn.addEventListener('click', saveFile);

   deleteFileBtn.addEventListener('click', deleteFile);

   newFileBtn.addEventListener('click', () => {
      newFileModal.style.display = 'block';
   });

   createFileBtn.addEventListener('click', createFile);

   submitPasswordBtn.addEventListener('click', () => {
      if (!currentFile) return;
      loadFileContent(currentFile.id, enterFilePassword.value);
   });

   confirmDeleteBtn.addEventListener('click', confirmDelete);

   fileSearch.addEventListener('input', (e) => {
      searchFiles(e.target.value);
   });

   isProtected.addEventListener('change', () => {
      passwordGroup.style.display = isProtected.checked ? 'block' : 'none';
   });

   document.querySelectorAll('.close, .close-modal').forEach(element => {
      element.addEventListener('click', () => {
         newFileModal.style.display = 'none';
         passwordModal.style.display = 'none';
         deleteModal.style.display = 'none';
      });
   });

   window.addEventListener('click', (e) => {
      if (e.target === newFileModal) {
         newFileModal.style.display = 'none';
      } else if (e.target === passwordModal) {
         passwordModal.style.display = 'none';
      } else if (e.target === deleteModal) {
         deleteModal.style.display = 'none';
      }
   });

   document.addEventListener('keydown', (e) => {
      if (e.ctrlKey && e.key === 's' && !saveFileBtn.disabled) {
         e.preventDefault();
         saveFile();
      }

      if (e.key === 'Escape') {
         newFileModal.style.display = 'none';
         passwordModal.style.display = 'none';
         deleteModal.style.display = 'none';
      }
   });
}

function formatDate(dateString) {
   const date = new Date(dateString);
   return date.toLocaleDateString('fa-IR');
}

function showError(message) {
   alert(message);
}

function showSuccess(message) {
   alert(message);
}
// Developer: DevZeus (Mahyar Asghari)