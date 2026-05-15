-- Rwandan History Platform Database Setup
-- Resetting the database to ensure all columns (like order_num) are correctly created
DROP DATABASE IF EXISTS rwanda_history_db;
CREATE DATABASE rwanda_history_db;
USE rwanda_history_db;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'trainer', 'admin') DEFAULT 'student',
    profile_image VARCHAR(255) DEFAULT 'default_user.png',
    login_code VARCHAR(10) UNIQUE, -- Unique code for trainers
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings Table (for Admin Signature, etc.)
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY DEFAULT 1,
    admin_signature TEXT, -- Base64 signature data
    site_name VARCHAR(255) DEFAULT 'Rwanda History Platform'
);

-- Activity Log Table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses/Modules Table
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    order_num INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lessons Table
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    content_type ENUM('text', 'video', 'audio', 'document') DEFAULT 'text',
    media_url VARCHAR(255),
    order_num INT,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- Exams Table
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT,
    title VARCHAR(255) NOT NULL,
    passing_score INT DEFAULT 70,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- Questions Table
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    question_text TEXT NOT NULL,
    question_type ENUM('mcq', 'tf', 'essay') DEFAULT 'mcq',
    options JSON, 
    correct_answer TEXT,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- User Progress
CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    lesson_id INT,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Exam Results
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    exam_id INT,
    score INT,
    status ENUM('pass', 'fail') DEFAULT 'fail',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Certificates
CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    module_id INT,
    cert_code VARCHAR(50) UNIQUE,
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- Forum Posts
CREATE TABLE forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed Data for Modules
INSERT INTO modules (title, description, thumbnail, order_num) VALUES
('Pre-Colonial Rwanda', 'The reign of Gihanga I Ngomijana, Ubwiru, and royal traditions.', 'module1.jpg', 1),
('Colonial Rwanda (1897–1962)', 'Role of Germans and Belgians, changes in governance structures.', 'module2.jpg', 2),
('Independence & First Republic (1962–1994)', 'Rwanda’s liberation and politics before the Genocide.', 'module3.jpg', 3),
('Genocide Against the Tutsi (1994)', 'Causes, structure, and testimonies of survivors.', 'module4.jpg', 4),
('Reconstruction & Unity (1994–2026)', 'National rebuilding, unity, and economic growth.', 'module5.jpg', 5);

-- Seed Data for Lessons
INSERT INTO lessons (module_id, title, content, content_type, order_num) VALUES
(1, 'The Legend of Gihanga', 'Gihanga I Ngomijana is the mythical first king of Rwanda, credited with unifying the nation and establishing royal traditions.', 'text', 1),
(1, 'Ubwiru: The Royal Code', 'Ubwiru was the secret code of rituals and traditions that governed the succession and behavior of kings.', 'text', 2),
(1, 'The Great Expansion', 'Under kings like Ruganzu II Ndoli and Kigeli IV Rwabugiri, Rwanda expanded its borders significantly through military strategy.', 'video', 3),
(2, 'The Arrival of Germans', 'In 1897, Captain Ramsay arrived in Rwanda, marking the beginning of German colonial presence.', 'text', 1),
(2, 'Belgian Mandate', 'After WWI, Belgium took over Rwanda as a League of Nations mandate, introducing identity cards based on ethnic classifications.', 'text', 2),
(3, 'The Path to Independence', 'Rwanda gained independence from Belgium on July 1, 1962, following the 1959 revolution.', 'text', 1),
(4, 'The 100 Days of Darkness', 'In 1994, over a million Tutsi were killed in 100 days. This section explores the causes and systemic nature of the genocide.', 'text', 1),
(5, 'The Vision 2020 & 2050', 'Post-1994 Rwanda focused on unity, reconciliation (Gacaca courts), and rapid economic development.', 'text', 1);
