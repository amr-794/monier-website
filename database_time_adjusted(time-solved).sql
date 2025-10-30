
-- =========================
-- إعداد النظام الزمني المضبوط لتوقيت القاهرة (UTC+2)
-- =========================

SET time_zone = '+02:00';

-- --- إعادة إنشاء القاعدة بضبط كامل لدعم اللغة العربية
DROP DATABASE IF EXISTS `chemistry1_db`;
CREATE DATABASE `chemistry1_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `chemistry1_db`;

-- --- جدول الإعدادات العامة للمنصة
CREATE TABLE `settings` (
  `setting_name` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` (`setting_name`, `setting_value`) VALUES
('mobile_only_mode', '0'),
('browser_message', '<h2>المشاهدة من التطبيق فقط</h2><p>عذراً، لا يمكنك مشاهدة المحاضرة من المتصفح. يرجى تحميل التطبيق الخاص بنا للمتابعة.</p>'),
('app_download_links', '[]'),
('app_apk_files', '[]');

-- --- جدول الأدمن
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` (`username`, `password_hash`)
VALUES ('amr', '$2a$12$7cItZZBxo93vSrYYQHv.ourEoiMDnF21DfslT9oMfX0pvD/h2BuB2'); -- 12345

-- --- جدول الطلاب
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `unique_student_id` VARCHAR(10) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `parent_phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `grade` ENUM('first_secondary', 'second_secondary', 'third_secondary') DEFAULT 'first_secondary',
  `last_login` DATETIME DEFAULT NULL,
  `last_seen` DATETIME DEFAULT NULL,
  `login_count` INT DEFAULT 0,
  `status` ENUM('active', 'suspended', 'banned') DEFAULT 'active',
  `suspend_until` DATETIME DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول أماكن التواجد
CREATE TABLE `locations` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `address` TEXT NOT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `working_hours` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `locations` (`name`, `address`, `latitude`, `longitude`, `working_hours`, `phone`) VALUES
('المقر الرئيسي', '123 شارع التحرير، وسط البلد، القاهرة', 30.044420, 31.235712, '9:00 ص - 5:00 م من السبت إلى الخميس', '+20 123 456 7890'),
('فرع المعادي', '456 شارع 9، المعادي، القاهرة', 29.966834, 31.250000, '10:00 ص - 6:00 م من الأحد إلى الخميس', '+20 123 456 7891'),
('فرع مدينة نصر', '789 شارع مصطفى النحاس، مدينة نصر، القاهرة', 30.050000, 31.330000, '8:00 ص - 4:00 م من السبت إلى الأربعاء', '+20 123 456 7892');

-- --- جدول قوائم التشغيل
CREATE TABLE `playlists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `grade` ENUM('first_secondary', 'second_secondary', 'third_secondary', 'all') DEFAULT 'all',
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول المحاضرات
CREATE TABLE `lectures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `playlist_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `thumbnail_path` VARCHAR(255),
  `price` DECIMAL(10,2) DEFAULT 0.00,
  `is_free` TINYINT(1) DEFAULT 0,
  `grade` ENUM('first_secondary', 'second_secondary', 'third_secondary', 'all') DEFAULT 'first_secondary',
  `media_type` ENUM('bunny', 'peertube', 'embed' , 'youtube' , 'gdrive' , 'local') NOT NULL,
  `media_source` TEXT NOT NULL,
  `bunny_library_id` VARCHAR(50),
  `max_views` INT NOT NULL DEFAULT 3,
  `is_active` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول الأكواد
CREATE TABLE `codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code_value` VARCHAR(100) NOT NULL UNIQUE,
  `lecture_id` INT NOT NULL,
  `is_used` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `used_by_student_id` INT,
  `used_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`lecture_id`) REFERENCES `lectures`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`used_by_student_id`) REFERENCES `students`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- صلاحيات وصول الطلاب
CREATE TABLE `student_lecture_access` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `lecture_id` INT NOT NULL,
  `remaining_views` INT NOT NULL,
  `last_viewed` DATETIME,
  UNIQUE KEY `student_lecture` (`student_id`, `lecture_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lecture_id`) REFERENCES `lectures`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول الإعلانات والإشعارات
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `is_visible` TINYINT(1) DEFAULT 1,
  `is_notification` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول قراءة الإشعارات من الطلاب
CREATE TABLE `student_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `announcement_id` INT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` DATETIME,
  UNIQUE KEY `student_notification` (`student_id`, `announcement_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`announcement_id`) REFERENCES `announcements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول مرفقات المحاضرات
CREATE TABLE `lecture_attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lecture_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`lecture_id`) REFERENCES `lectures`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول الاختبارات
CREATE TABLE `quizzes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lecture_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `time_limit_minutes` INT NOT NULL DEFAULT 30,
  `is_active` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`lecture_id`) REFERENCES `lectures`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جداول الأسئلة والخيارات
CREATE TABLE `quiz_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `question_text` TEXT NOT NULL,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `question_options` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_id` INT NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `is_correct` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`question_id`) REFERENCES `quiz_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول محاولات الطلاب
CREATE TABLE `student_quiz_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `quiz_id` INT NOT NULL,
  `score` INT NOT NULL,
  `total_questions` INT NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `one_attempt_per_student` (`student_id`, `quiz_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول تتبع تقدم الطالب في الاختبار
CREATE TABLE `student_quiza_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `quiz_id` INT NOT NULL,
  `time_remaining_seconds` INT NOT NULL,
  `answers_json` TEXT,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `student_progress` (`student_id`, `quiz_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- جدول الجلسات
CREATE TABLE `user_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `user_type` ENUM('student', 'admin') NOT NULL,
  `session_id` VARCHAR(128) NOT NULL UNIQUE,
  `login_time` DATETIME NOT NULL,
  `last_activity` DATETIME NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  INDEX `user_id_type` (`user_id`, `user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
