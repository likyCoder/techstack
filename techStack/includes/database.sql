-- Create the database
CREATE DATABASE IF NOT EXISTS eduportal;
USE eduportal;

-- Create users table for authentication (must come first as it's referenced by others)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create classes table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    class_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    class_id INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create lessons table
CREATE TABLE IF NOT EXISTS lessons (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create enrollment table (users to classes relationship)
CREATE TABLE IF NOT EXISTS enrollments (
    user_id INT NOT NULL,
    class_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subject enrollments table
CREATE TABLE IF NOT EXISTS subject_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_subject_enrollment (user_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create lesson progress table
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    status ENUM('completed', 'in-progress', 'not-started') DEFAULT 'not-started',
    progress_percent INT DEFAULT 0,
    last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_lesson (user_id, lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create resources table for subject materials
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    url VARCHAR(255),
    subject_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create achievements table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date_achieved DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user activity table
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_title VARCHAR(255) DEFAULT 'Activity',
    activity_description TEXT NOT NULL,
    activity_date DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create class schedules table
CREATE TABLE IF NOT EXISTS class_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    schedule_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    topic VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subject sessions table
CREATE TABLE IF NOT EXISTS subject_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    session_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 45,
    topic VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create assignments table
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    assignment_title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    max_score INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add all foreign key constraints AFTER all tables are created
ALTER TABLE classes ADD CONSTRAINT fk_class_creator 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE subjects ADD CONSTRAINT fk_subject_class 
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE;
    
ALTER TABLE subjects ADD CONSTRAINT fk_subject_creator 
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE lessons ADD CONSTRAINT fk_class_lesson 
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE;

ALTER TABLE enrollments ADD CONSTRAINT fk_enrollment_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
    
ALTER TABLE enrollments ADD CONSTRAINT fk_enrollment_class 
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE;

ALTER TABLE subject_enrollments ADD CONSTRAINT fk_subject_enrollment_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
    
ALTER TABLE subject_enrollments ADD CONSTRAINT fk_subject_enrollment_subject 
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE;

ALTER TABLE lesson_progress ADD CONSTRAINT fk_progress_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
    
ALTER TABLE lesson_progress ADD CONSTRAINT fk_progress_lesson 
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE;

ALTER TABLE resources ADD CONSTRAINT fk_resource_subject 
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE;
    
ALTER TABLE resources ADD CONSTRAINT fk_resource_uploader 
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE achievements ADD CONSTRAINT fk_achievement_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE user_activity ADD CONSTRAINT fk_activity_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE class_schedules ADD CONSTRAINT fk_schedule_class 
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE;

ALTER TABLE subject_sessions ADD CONSTRAINT fk_session_subject 
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE;

ALTER TABLE assignments ADD CONSTRAINT fk_assignment_subject 
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE;

-- Insert sample data in proper order
START TRANSACTION;


INSERT IGNORE INTO classes (class_name, class_code, description, created_by)
VALUES 
('Computer Science Basics', 'CS101', 'Introduction to computers and programming fundamentals.', 1),
('Advanced Web Development', 'WD202', 'Covers frontend and backend frameworks in depth.', 2),
('Database Systems', 'DB301', 'Relational databases, MySQL, and normalization techniques.', 1),
('Networking Essentials', 'NET150', 'Covers basics of computer networks and protocols.', 3),
('Software Engineering Principles', 'SE205', 'Software development lifecycle, testing, and maintenance.', 2),
('Mobile App Development', 'MAD210', 'Developing mobile apps using Android and Flutter.', 1),
('Artificial Intelligence', 'AI410', 'Concepts in AI, including machine learning and neural networks.', 3);


-- First insert users (required for all other tables)
INSERT INTO users (id, username, email, password, first_name, last_name, role) VALUES
(1, 'admin', 'admin@eduportal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin'),
(2, 'teacher1', 'teacher1@eduportal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'teacher'),
(3, 'student1', 'student1@eduportal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice', 'Johnson', 'student');

-- Then insert classes (requires users)
INSERT INTO classes (id, class_name, class_code, description, created_by) VALUES 
(1, 'Mathematics 101', 'MATH101', 'Introduction to Algebra and Geometry', 2),
(2, 'Computer Science Fundamentals', 'CS101', 'Basic programming concepts and algorithms', 2),
(3, 'English Literature', 'ENG201', 'Classic and contemporary literature studies', 2);

-- Then insert subjects (requires classes and users)
INSERT INTO subjects (id, subject_name, subject_code, description, class_id, created_by) VALUES 
(1, 'Algebra', 'MATH101-ALG', 'Fundamentals of algebraic equations', 1, 2),
(2, 'Geometry', 'MATH101-GEO', 'Basic geometric principles and theorems', 1, 2),
(3, 'Python Programming', 'CS101-PYT', 'Introduction to Python programming language', 2, 2),
(4, 'Data Structures', 'CS101-DS', 'Basic data structures and their implementations', 2, 2),
(5, 'Shakespeare Studies', 'ENG201-SHA', 'Analysis of Shakespearean works', 3, 2),
(6, 'Modern Poetry', 'ENG201-POE', 'Study of 20th century poetry', 3, 2);

INSERT INTO subjects (subject_name, subject_code, description, class_id, created_by) VALUES
('Calculus I', 'MATH101-CAL1', 'Differentiation and basic integration concepts', 1, 2),
('Probability & Statistics', 'MATH101-STAT', 'Basics of probability, distributions, and descriptive statistics', 1, 2),
('Linear Algebra', 'MATH101-LA', 'Vectors, matrices, and linear transformations', 1, 2),
('Trigonometry', 'MATH101-TRIG', 'Trigonometric functions, identities, and equations', 1, 2),
('Mathematical Logic', 'MATH101-LOG', 'Propositions, truth tables, and formal reasoning', 1, 2);


-- Then insert lessons (requires classes)
INSERT INTO lessons (id, class_id, title, description) VALUES
(1, 1, 'Introduction to Algebra', 'Basic algebraic concepts and equations'),
(2, 1, 'Linear Equations', 'Solving linear equations with one variable'),
(3, 2, 'Python Basics', 'Introduction to Python syntax and structure');

-- Then insert enrollments (requires users and classes)
INSERT INTO enrollments (user_id, class_id) VALUES 
(3, 1),
(3, 2),
(3, 3);

-- Then insert subject enrollments (requires users and subjects)
INSERT INTO enrollments (user_id, class_id)
VALUES 
    (1, 1),
    (2, 2),
    (3, 3),
    (4, 1);


-- Then insert lesson progress (requires users and lessons)
INSERT INTO lesson_progress (user_id, lesson_id, status, progress_percent) VALUES 
(3, 1, 'in-progress', 50),
(3, 2, 'not-started', 0),
(3, 3, 'completed', 100);

-- Then insert resources (requires subjects and users)
INSERT INTO resources (title, description, file_path, subject_id, uploaded_by) VALUES 
('Algebra Basics PDF', 'Introduction to algebraic concepts', '/uploads/algebra-basics.pdf', 1, 2),
('Geometry Workbook', 'Practice problems for geometry', '/uploads/geometry-workbook.pdf', 2, 2),
('Python Cheat Sheet', 'Quick reference for Python syntax', '/uploads/python-cheatsheet.pdf', 3, 2),
('Shakespeare Sonnets', 'Collection of Shakespeare sonnets', '/uploads/sonnets.pdf', 5, 2);

-- Then insert achievements (requires users)
INSERT INTO achievements (user_id, title, description, date_achieved) VALUES
(3, 'First Assignment', 'Completed first assignment successfully', CURDATE());

-- Then insert user activity (requires users)
INSERT INTO user_activity (user_id, activity_title, activity_description) VALUES
(1, 'Login', 'Logged in to the system'),
(1, 'View', 'Viewed class materials'),
(1, 'Submission', 'Submitted an assignment'),
(3, 'Login', 'Logged in to the system'),
(3, 'Lesson', 'Completed lesson on Python Basics');

-- Then insert class schedules (requires classes)
INSERT INTO class_schedules (class_id, schedule_date, duration_minutes, topic) VALUES
(1, DATE_ADD(NOW(), INTERVAL 1 DAY), 60, 'Algebra Basics'),
(2, DATE_ADD(NOW(), INTERVAL 2 DAY), 90, 'Python Introduction');

-- Then insert subject sessions (requires subjects)
INSERT INTO subject_sessions (subject_id, session_date, duration_minutes, topic) VALUES
(1, DATE_ADD(NOW(), INTERVAL 3 DAY), 45, 'Linear Equations'),
(3, DATE_ADD(NOW(), INTERVAL 4 DAY), 60, 'Data Types');

-- Then insert assignments (requires subjects)
INSERT INTO assignments (subject_id, assignment_title, description, due_date, max_score) VALUES
(1, 'Algebra Basics', 'Solve simple algebraic equations', DATE_ADD(NOW(), INTERVAL 7 DAY), 100),
(3, 'Python Exercises', 'Complete basic Python exercises', DATE_ADD(NOW(), INTERVAL 10 DAY), 50);

COMMIT;

-- Verify data was inserted correctly
SELECT 
    (SELECT COUNT(*) FROM users) AS user_count,
    (SELECT COUNT(*) FROM classes) AS class_count,
    (SELECT COUNT(*) FROM subjects) AS subject_count,
    (SELECT COUNT(*) FROM enrollments) AS enrollment_count,
    (SELECT COUNT(*) FROM user_activity) AS activity_count;



















    CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    class_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    description TEXT,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    class_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, class_id)
);

CREATE TABLE IF NOT EXISTS subject_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    progress INT DEFAULT 0,
    last_accessed TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, subject_id)
);









-- Quizzes table
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    quiz_title VARCHAR(255) NOT NULL,
    description TEXT,
    time_limit INT DEFAULT NULL COMMENT 'Time limit in minutes',
    attempts_allowed INT DEFAULT 1 COMMENT '0 for unlimited attempts',
    is_published BOOLEAN DEFAULT TRUE,
    passing_score INT DEFAULT 70 COMMENT 'Percentage required to pass',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- Quiz Questions table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'essay') NOT NULL,
    question_order INT NOT NULL DEFAULT 0,
    points INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz Options table (for multiple choice questions)
CREATE TABLE IF NOT EXISTS quiz_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Quiz Attempts table
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    score DECIMAL(5,2) NULL,
    time_taken INT NULL COMMENT 'Time taken in seconds',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz Answers table
CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_text TEXT NULL,
    option_id INT NULL,
    is_correct BOOLEAN NULL,
    points_earned DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES quiz_options(id) ON DELETE SET NULL
);


-- Sample quiz for Algebra lesson
INSERT INTO quizzes (lesson_id, quiz_title, description, time_limit, attempts_allowed, passing_score) VALUES
(1, 'Algebra Basics Quiz', 'Test your understanding of basic algebra concepts', 30, 3, 70);

-- Sample questions for the quiz
INSERT INTO quiz_questions (quiz_id, question_text, question_type, question_order, points) VALUES
(1, 'What is the solution for x in the equation: 2x + 5 = 15?', 'multiple_choice', 1, 1),
(1, 'In algebra, a variable is a symbol that represents an unknown value.', 'true_false', 2, 1),
(1, 'Simplify the expression: 3(x + 4) - 2x', 'short_answer', 3, 2),
(1, 'Explain the difference between an expression and an equation in algebra.', 'essay', 4, 3);

-- Options for multiple choice question
INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES
(1, 'x = 5', TRUE, 1),
(1, 'x = 10', FALSE, 2),
(1, 'x = 7.5', FALSE, 3),
(1, 'x = 20', FALSE, 4);

-- Options for true/false question (handled differently in code)
INSERT INTO quiz_options (question_id, option_text, is_correct, option_order) VALUES
(2, 'True', TRUE, 1),
(2, 'False', FALSE, 2);

-- Sample quiz attempt
INSERT INTO quiz_attempts (user_id, quiz_id, completed, completed_at, score, time_taken) VALUES
(1, 1, TRUE, NOW(), 75.00, 1200);

-- Sample answers for the attempt
INSERT INTO quiz_answers (attempt_id, question_id, option_id, is_correct, points_earned) VALUES
(1, 1, 1, TRUE, 1),
(1, 2, 5, TRUE, 1);

INSERT INTO quiz_answers (attempt_id, question_id, answer_text, points_earned) VALUES
(1, 3, 'x + 12', 2),
(1, 4, 'An expression is a combination of numbers and variables without an equals sign, while an equation has an equals sign showing equality between two expressions.', 1);



ALTER TABLE lessons ADD subject_id INT(11);
ALTER TABLE lessons
ADD CONSTRAINT fk_lesson_subject
FOREIGN KEY (subject_id) REFERENCES subjects(id);

 












