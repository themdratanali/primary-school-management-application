-- Apex Model School Database
-- Database Name: apex

-- Create Database
CREATE DATABASE IF NOT EXISTS apex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apex;

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    number VARCHAR(20),
    photo VARCHAR(255) DEFAULT 'default.png',
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Batches Table
CREATE TABLE IF NOT EXISTS batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Classes Table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100),
    total_mark INT,
    class_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teachers Table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    designation VARCHAR(255),
    mother_name VARCHAR(255),
    father_name VARCHAR(255),
    gender ENUM('Male','Female','Other'),
    dob DATE,
    blood_group VARCHAR(10),
    religion VARCHAR(100),
    nationality VARCHAR(100),
    nid VARCHAR(100),
    present_address TEXT,
    permanent_address TEXT,
    education TEXT,
    institute VARCHAR(255),
    result VARCHAR(255),
    experience TEXT,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Table
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    mother_name VARCHAR(255),
    father_name VARCHAR(255),
    gender ENUM('Male','Female','Other'),
    dob DATE,
    blood_group VARCHAR(10),
    religion VARCHAR(100),
    nationality VARCHAR(100),
    nid VARCHAR(100),
    present_address TEXT,
    permanent_address TEXT,
    education TEXT,
    experience TEXT,
    phone VARCHAR(50) NOT NULL,
    designation VARCHAR(100),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teacher Subjects Table
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    mother_name VARCHAR(255),
    father_name VARCHAR(255),
    gender ENUM('Male','Female','Other'),
    dob DATE,
    blood_group VARCHAR(10),
    religion VARCHAR(100),
    nationality VARCHAR(100),
    present_address TEXT,
    permanent_address TEXT,
    roll INT NOT NULL,
    batch_id INT,
    class_id INT,
    father_mobile VARCHAR(50),
    mother_mobile VARCHAR(50),
    guardian_name VARCHAR(255),
    guardian_profession VARCHAR(255),
    guardian_mobile VARCHAR(50),
    guardian_address TEXT,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Users Table
CREATE TABLE IF NOT EXISTS student_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    login_password VARCHAR(255),
    plain_password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Results Table
CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    batch_id INT,
    class_id INT,
    subject_id INT NOT NULL,
    marks VARCHAR(50),
    exam_type VARCHAR(50),
    exam_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fee Types Table
CREATE TABLE IF NOT EXISTS fee_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Admin (username: apex, password: 484406)
INSERT INTO admins (name, email, number, photo, username, password) 
SELECT 'Apex', 'milonk165@gmail.com', '0123456789', 'default.png', 'apex', '$2y$10$lP0hNhqWMhyy0lqudY0vve5u9r1JEwHalBGUmfHakNuoAYdJhmBam'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'apex');

-- Syllabus Files Table
CREATE TABLE IF NOT EXISTS syllabus_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(255),
    INDEX idx_date (uploaded_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Routine Files Table
CREATE TABLE IF NOT EXISTS routine_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(255),
    INDEX idx_date (uploaded_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exam Results Files Table
CREATE TABLE IF NOT EXISTS exam_results_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by VARCHAR(255),
    INDEX idx_date (uploaded_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Documents Table
CREATE TABLE IF NOT EXISTS documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reference_type ENUM('teacher', 'staff', 'student') NOT NULL,
    reference_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teachers Documents Table
CREATE TABLE IF NOT EXISTS teachers_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Documents Table
CREATE TABLE IF NOT EXISTS staff_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Students Documents Table
CREATE TABLE IF NOT EXISTS students_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sections Table
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    class_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- HomeWork Table
CREATE TABLE IF NOT EXISTS homeworks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    class_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    details TEXT,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_type VARCHAR(50),
    teacher_id INT NOT NULL,
    status ENUM('active', 'done') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_batch_class (batch_id, class_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
