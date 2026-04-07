
-- ============================================
-- Smart Reminder System - Database Schema
-- Campus Activities Academic Calendar
-- ============================================

CREATE DATABASE IF NOT EXISTS smart_reminder_db;
USE smart_reminder_db;

-- -------------------------------------------
-- Users Table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    role ENUM('student', 'lecturer', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Events Table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    department VARCHAR(100) DEFAULT 'All',
    category ENUM('lecture', 'exam', 'registration', 'seminar', 'workshop', 'deadline', 'other') NOT NULL DEFAULT 'other',
    reminder_days INT NOT NULL DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Notifications Table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('email', 'system') NOT NULL DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------
-- Indexes for Performance
-- -------------------------------------------
CREATE INDEX idx_events_date ON events(event_date);
CREATE INDEX idx_events_department ON events(department);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_notifications_sent ON notifications(sent_at);

-- -------------------------------------------
-- Seed: Default Admin User
-- Password: Admin@123 (bcrypt hash)
-- -------------------------------------------
INSERT INTO users (name, email, password, department, role) VALUES
('System Admin', 'admin@campus.edu', '$2y$10$bx1X0ouj0pJvuxHgTpDDqu0XhopkucSbBZYK6w.UT1Vk226IvXmqq', 'Administration', 'admin');
