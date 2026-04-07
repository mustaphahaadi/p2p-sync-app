# 📅 Smart Reminder System for Campus Activities

A web application that sends automatic reminders to students and lecturers about important academic events based on the school's academic calendar.

---

## ✨ Features

- **User Authentication** — Register, Login, Logout with role-based access (Student, Lecturer, Admin)
- **Event Management** — Full CRUD for academic calendar events (Admin)
- **Automated Reminders** — Daily cron script checks events and sends email reminders
- **Notification Center** — In-app notification list with read/unread status
- **Dashboard** — Upcoming events, today's events, stats, and recent notifications
- **User Management** — Admin panel for managing users and roles
- **Profile Management** — Users can update profile info and change passwords
- **Department Filtering** — Events are filtered by user's department

---

## 🛠 Tech Stack

| Layer         | Technology          |
|---------------|---------------------|
| Frontend      | HTML, CSS, Bootstrap 5.3 |
| Backend       | PHP 8.0+            |
| Database      | MySQL 5.7+          |
| Server        | Apache (with mod_rewrite) |
| Notifications | Email (PHP mail())  |
| Scheduler     | Cron Job            |

---

## 📁 Project Structure

```
p2p-sync-app/
├── assets/css/style.css         # Custom design system
├── admin/users.php              # User management (admin)
├── auth/
│   ├── login.php                # Login page
│   ├── register.php             # Registration page
│   └── logout.php               # Logout handler
├── config/
│   ├── database.php             # Database connection (PDO)
│   └── mail.php                 # Email config and templates
├── cron/
│   ├── send_reminders.php       # Daily reminder cron script
│   └── cron.log                 # Cron output log
├── database/schema.sql          # MySQL schema and seed data
├── events/
│   ├── index.php                # Events listing with filters
│   ├── create.php               # Add event (admin)
│   ├── edit.php                 # Edit event (admin)
│   └── delete.php               # Delete event (admin)
├── includes/
│   ├── functions.php            # Core functions and session
│   ├── header.php               # Shared navbar and head
│   └── footer.php               # Shared footer and scripts
├── index.php                    # Entry point (redirects)
├── dashboard.php                # Main dashboard
├── notifications.php            # User notifications
├── profile.php                  # User profile and settings
├── .htaccess                    # Apache security config
└── README.md
```

---

## 🚀 Setup Instructions

### Prerequisites

- PHP 8.0+ with PDO MySQL extension
- MySQL 5.7+ or MariaDB
- Apache with mod_rewrite enabled

### 1. Create Database

```bash
mysql -u root -p < database/schema.sql
```

This creates the `smart_reminder_db` database with all tables and a default admin user.

### 2. Configure Database

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_reminder_db');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### 3. Configure Email

Edit `config/mail.php` with your SMTP settings.

### 4. Set Up Cron Job

Add this to your crontab (`crontab -e`):

```cron
0 7 * * * /usr/bin/php /path/to/project/cron/send_reminders.php >> /path/to/project/cron/cron.log 2>&1
```

### 5. Access the Application

```
http://localhost/smart-reminder/
```

---

## 🔑 Default Admin Login

| Field    | Value              |
|----------|--------------------|
| Email    | admin@campus.edu   |
| Password | Admin@123          |
| Role     | Admin              |

---

## 🔔 How Reminders Work

1. Admin creates an event with a **reminder_days** value (e.g., 3 days)
2. The cron job runs daily and checks: `IF today == event_date - reminder_days`
3. Matching users (by department) receive an **email** and **in-app notification**
4. Duplicate prevention ensures one reminder per user per event per day

---

## 📋 Database Tables

### `users`
| Column     | Type         | Description              |
|------------|--------------|--------------------------|
| id         | INT (PK)     | Auto-increment           |
| name       | VARCHAR(100) | Full name                |
| email      | VARCHAR(150) | Unique email             |
| password   | VARCHAR(255) | Bcrypt hashed            |
| department | VARCHAR(100) | User department          |
| role       | ENUM         | student/lecturer/admin   |

### `events`
| Column        | Type         | Description              |
|---------------|--------------|--------------------------|
| id            | INT (PK)     | Auto-increment           |
| title         | VARCHAR(200) | Event name               |
| description   | TEXT         | Event details            |
| event_date    | DATE         | Date of event            |
| event_time    | TIME         | Optional time            |
| department    | VARCHAR(100) | Target department or All |
| category      | ENUM         | lecture/exam/etc         |
| reminder_days | INT          | Days before to remind    |
| created_by    | INT (FK)     | Admin who created it     |

### `notifications`
| Column   | Type      | Description              |
|----------|-----------|--------------------------|
| id       | INT (PK)  | Auto-increment           |
| user_id  | INT (FK)  | Target user              |
| event_id | INT (FK)  | Related event            |
| message  | TEXT      | Notification text        |
| type     | ENUM      | email/system             |
| is_read  | TINYINT   | 0=unread, 1=read         |
| sent_at  | TIMESTAMP | When sent                |

---

## 🔒 Security Features

- CSRF token protection on all forms
- Password hashing with bcrypt
- Session regeneration on login
- Input sanitization (htmlspecialchars)
- Prepared statements (PDO) for SQL injection prevention
- .htaccess blocks access to sensitive directories
- Role-based access control (RBAC)

---

## 📝 License

This project is developed for academic purposes (HND Project).
