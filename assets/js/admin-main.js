
// وظائف JavaScript للوحة تحكم الأدمن

document.addEventListener('DOMContentLoaded', function() {
    // إدارة علامات التبويب
    initTabs();
    
    // إدارة رفع الملفات
    initFileUploads();
    
    // تحديث الإحصائيات تلقائياً
    initAutoRefresh();
    
    // إدارة التنبيهات
    initAlerts();
});

// تهيئة علامات التبويب
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            
            // إزالة النشاط من جميع علامات التبويب
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // إضافة النشاط للعلامة المحددة
            this.classList.add('active');
            if (target) {
                document.getElementById(target).classList.add('active');
            }
        });
    });
}

// تهيئة رفع الملفات
function initFileUploads() {
    const fileUploads = document.querySelectorAll('.file-upload');
    
    fileUploads.forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        const label = upload.querySelector('.file-label') || upload;
        
        if (input) {
            label.addEventListener('click', function() {
                input.click();
            });
            
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const fileName = this.files[0].name;
                    const fileSize = (this.files[0].size / (1024 * 1024)).toFixed(2);
                    
                    // تحديث الواجهة
                    const existingInfo = upload.querySelector('.file-info');
                    if (existingInfo) {
                        existingInfo.remove();
                    }
                    
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'file-info';
                    fileInfo.innerHTML = `
                        <p><strong>${fileName}</strong></p>
                        <small>الحجم: ${fileSize} MB</small>
                    `;
                    upload.appendChild(fileInfo);
                }
            });
        }
    });
}

// التحديث التلقائي للإحصائيات
function initAutoRefresh() {
    const statsGrid = document.querySelector('.stats-grid');
    if (statsGrid) {
        // تحديث كل 30 ثانية
        setInterval(updateStats, 30000);
    }
}

async function updateStats() {
    try {
        const response = await fetch('api/get_stats.php');
        const data = await response.json();
        
        if (data.success) {
            // تحديث الأرقام في الواجهة
            document.querySelectorAll('.stat-number').forEach((element, index) => {
                const statKey = element.closest('.stat-card').getAttribute('data-stat');
                if (data[statKey]) {
                    element.textContent = data[statKey];
                    
                    // تأثير التحديث
                    element.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        element.style.transform = 'scale(1)';
                    }, 300);
                }
            });
        }
    } catch (error) {
        console.error('Error updating stats:', error);
    }
}

// إدارة التنبيهات
function initAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // إضافة زر إغلاق للتنبيهات
        if (!alert.querySelector('.alert-close')) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'alert-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.addEventListener('click', function() {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
            alert.appendChild(closeBtn);
        }
        
        // إخفاء التنبيه تلقائياً بعد 5 ثوانٍ
        if (alert.classList.contains('alert-auto-close')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        }
    });
}

// وظائف مساعدة
function confirmAction(message) {
    return confirm(message || 'هل أنت متأكد من تنفيذ هذا الإجراء؟');
}

function showLoading(element) {
    element.disabled = true;
    element.innerHTML = '<span class="loading"></span> جاري المعالجة...';
}

function hideLoading(element, originalText) {
    element.disabled = false;
    element.textContent = originalText;
}

// إدارة الجلسات
function checkSession() {
    fetch('api/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.valid) {
                document.getElementById('session-alert').style.display = 'flex';
            }
        })
        .catch(error => {
            console.error('Session check error:', error);
        });
}

// التحقق من الجلسة كل دقيقة
setInterval(checkSession, 60000);

// نسخ النص إلى الحافظة
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // إظهار رسالة نجاح
        showToast('تم نسخ النص إلى الحافظة');
    }, function(err) {
        console.error('Failed to copy: ', err);
    });
}

// إظهار رسائل toast
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// إضافة أنماط الـ toast
const toastStyles = document.createElement('style');
toastStyles.textContent = `
.toast {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(-100px);
    background: #333;
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    z-index: 10000;
    transition: all 0.3s ease;
    opacity: 0;
}

.toast.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}

.toast-success { background: var(--success-color); }
.toast-error { background: var(--danger-color); }
.toast-warning { background: var(--warning-color); }
`;
document.head.appendChild(toastStyles);
