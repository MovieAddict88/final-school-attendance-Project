# Student Management System — Wireframe & Auto-Installer (PHP + MySQL)

> Professional, fluid, modern and responsive design. Ready-to-copy wireframe, database schema, folder structure, `config.php` template and a `setup.php` autoinstall script that *attempts* to write `config.php` for you (with a safe fallback if server permissions prevent automatic write).

---

## 1) Project Goals
- Single-codebase PHP (vanilla PHP, no frameworks) ready for beginners and small schools.
- Modern responsive UI wireframe for Desktop / Tablet / Mobile.
- Four portals: **Super Admin**, **Teacher**, **Parent**, **Student**.
- Auto-setup script to create DB and initial super-admin and generate `config.php` (falls back to manual file content if write not allowed).
- Security basics included: prepared statements, password hashing, simple role checks, .htaccess recommendations.

---

## 2) Main Features (by role)
### Super Admin (Administration portal)
- Add Teacher
- Add Announcement
- Manage all teachers (CRUD)
- Manage all students (CRUD) — filter by teacher, grade, section
- Upload school logo & seal
- Global settings (terms, school year, quarters)

### Teacher Portal
- Add Section
- Add Grade Levels
- Daily Attendance (mark students present/absent/late)
- Add Quiz (create, view responses)
- Add Assignment (upload files / create text task)
- Add Announcement (visible to students/parents)

### Parent Portal
- View child(ren) record (read-only)
- View attendance
- Message from teacher (inbox)

### Student Portal
- View Daily Record (attendance summary)
- View Quiz (assigned, results)
- View Announcements
- View Assignments
- View Final Grade per Quarter

---

## 3) Header & Sidebar (global wireframe)
**Header (top)** — fixed, 60px height: school logo (left), school name + place (center), small user avatar + quick actions (right).

**Sidebar (left)** — collapsible on small screens, contains profile image (uploaded), role badge, nav links:
- Dashboard
- Students
- Attendance
- Grades / Quizzes / Assignments
- Announcements
- Messages
- Settings (Admin only)

**Mobile behaviour**: top hamburger always visible, not hidden by pinch. The hamburger toggles the sidebar overlay; profile icon visible in header always.

---

## 4) Page Wireframes (brief)
**Dashboard (per role)**
- KPIs: total students, present today, pending assignments, unread messages
- Recent activity stream

**Students list**
- Table with search, filter (grade, section, teacher), export CSV button
- Each row: avatar, name, ID, grade, section, teacher, actions (view/edit/delete)

**Attendance**
- Select date / section / grade dropdown. Teacher selects students checkboxes.
- Quick Mark All Present / Absent

**Quiz & Assignment forms**
- Title, Description, Grade, Section, Due Date, File Upload
- Attach optional audio/image

**Announcements**
- Simple title + short body + visibility (All / Teachers / Students / Parents)

**Profile**
- Display user photo, contact, children (for parents), homeroom (for teacher)

---

## 5) Responsive Layout Guidelines
- Use a 12-column grid (CSS grid or flexbox). Breakpoints: 1280px, 1024px, 768px, 480px.
- Sidebar: full left on >= 1024px; collapsible on 768–1024; overlay on <768 with always-visible hamburger.
- Large touch targets (min 44px) for mobile buttons.
- Use icon + label in sidebar for larger screens; icon-only for very small screens.

---

## 6) Database Schema (core tables)
```sql
-- users: holds all user accounts (admin/teacher/parent/student)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('superadmin','teacher','parent','student') NOT NULL,
  email VARCHAR(150),
  fullname VARCHAR(200),
  avatar VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE teachers (
  id INT PRIMARY KEY,
  teacher_code VARCHAR(50),
  phone VARCHAR(50),
  bio TEXT,
  FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE parents (
  id INT PRIMARY KEY,
  phone VARCHAR(50),
  FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE students (
  id INT PRIMARY KEY,
  student_code VARCHAR(50),
  grade_level INT,
  section VARCHAR(100),
  homeroom_teacher_id INT,
  birthdate DATE,
  parent_id INT,
  FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (homeroom_teacher_id) REFERENCES users(id),
  FOREIGN KEY (parent_id) REFERENCES users(id)
);

CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  teacher_id INT NOT NULL,
  date DATE NOT NULL,
  status ENUM('present','absent','late','excused') NOT NULL,
  note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id),
  FOREIGN KEY (teacher_id) REFERENCES users(id)
);

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_id INT,
  title VARCHAR(255),
  body TEXT,
  visibility ENUM('all','teachers','students','parents') DEFAULT 'all',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE quizzes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT,
  title VARCHAR(255),
  description TEXT,
  grade_level INT,
  section VARCHAR(100),
  due_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id INT,
  title VARCHAR(255),
  description TEXT,
  file_path VARCHAR(255),
  grade_level INT,
  section VARCHAR(100),
  due_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT,
  receiver_id INT,
  subject VARCHAR(255),
  body TEXT,
  is_read TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  teacher_id INT,
  quiz_id INT NULL,
  assignment_id INT NULL,
  grade FLOAT,
  quarter INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

> Note: add indexes on frequently-searched columns (grade_level, section, teacher_id).

---

## 7) Project File Structure (suggested)
```
/project-root/
  /public/                 # web root (serve this directory)
    index.php
    login.php
    logout.php
    /assets/
      /css/
      /js/
      /images/
  /app/
    /controllers/
    /models/
    /views/
  /config/                 # generated config.php will live here
    config.php (auto-created by setup)
  /storage/
    /uploads/
  setup.php                # auto installer script
  README.md
  .htaccess
```

---

## 8) `config.php` Template (this will be generated by setup)
```php
<?php
// config.php - database configuration (auto-created by setup)
return [
  'db_host' => 'localhost',
  'db_name' => 'sms_db',
  'db_user' => 'root',
  'db_pass' => '',
  'base_url' => 'http://localhost/project-root/public'
];
```

> The app should `include __DIR__ . '/../config/config.php'` and use the returned array.

---

## 9) `setup.php` — Auto Installer (safe, will try to write config but fallbacks if permission denied)
> Place `setup.php` in project root (NOT public). Access it once to create DB + tables + initial superadmin.

```php
<?php
// setup.php - run once to create DB and config
if (php_sapi_name() === 'cli') {
  echo "Run from browser."; exit;
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sms_db';

try {
  $pdo = new PDO("mysql:host=$host", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $pdo->exec("USE `$dbname`");

  // create a minimal users table (you may expand using the schema above)
  $pdo->exec(file_get_contents(__DIR__.'/sql/schema_core.sql'));

  // create initial superadmin
  $username = 'admin';
  $password = password_hash('Admin@123', PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (username,password,role,fullname,email) VALUES (?, ?, ?, ?, ?)');
  $stmt->execute([$username, $password, 'superadmin', 'School Administrator', 'admin@example.com']);

  // prepare config content
  $config = <<<PHP
<?php
return [
  'db_host' => '$host',
  'db_name' => '$dbname',
  'db_user' => '$user',
  'db_pass' => '$pass',
  'base_url' => 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['REQUEST_URI']) . '/public'
];
PHP;

  \$cfgPath = __DIR__ . '/config/config.php';
  if (!is_dir(dirname(\$cfgPath))) mkdir(dirname(\$cfgPath), 0755, true);
  \$written = @file_put_contents(\$cfgPath, \$config);
  if (\$written === false) {
    echo "Setup completed but could not write config file due to permissions.\n";
    echo "Please create the file 'config/config.php' with the following content and save it manually:\n\n";
    echo htmlspecialchars(\$config);
  } else {
    echo "Setup completed and config/config.php created successfully.";
  }

} catch (PDOException $e) {
  echo "Error: " . $e->getMessage();
}

```

> Notes: `sql/schema_core.sql` should contain the SQL `CREATE TABLE` statements (core subset). The installer tries to write `config/config.php`. If the server's file permissions prevent write, it prints the exact content so user can copy-paste.

---

## 10) `sql/schema_core.sql` — Minimal content (example)
```sql
-- sql/schema_core.sql (put in project root /sql folder)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('superadmin','teacher','parent','student') NOT NULL,
  email VARCHAR(150),
  fullname VARCHAR(200),
  avatar VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 11) Security & Permissions Tips (avoid permission denied)
- Ensure `config/` has write permission during setup (e.g., `chmod 755 config/` or `chmod 775 config/`).
- After `config.php` is created, set safer permissions: `chmod 640 config/config.php`.
- `storage/uploads` should be writable by the webserver user (e.g., `chmod 775 storage/uploads`).
- Never store DB credentials in the webroot; keep `config/` outside `public/`.

---

## 12) Example: Basic DB connection helper (PDO) — `app/helpers/db.php`
```php
<?php
// returns a PDO using config
\$cfg = require __DIR__ . '/../../config/config.php';
try {
  \$pdo = new PDO(
    "mysql:host={\$cfg['db_host']};dbname={\$cfg['db_name']};charset=utf8mb4",
    \$cfg['db_user'],
    \$cfg['db_pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (PDOException \$e) {
  die('DB Connection failed: ' . \$e->getMessage());
}
return \$pdo;
```

---

## 13) UI / CSS Recommendations
- Use TailwindCSS or a small utility CSS (you can vendor it under `public/assets/css/` for no-build deployments).
- Use icon set (Feather or Font Awesome) via local copy to avoid remote calls.
- A modern color system: neutral background, accent color (#2B6CB0 or similar), soft shadows, rounded corners (8px+), consistent spacing.
- Typography: system font stack for performance.

---

## 14) Implementation Checklist / Next Steps
1. Copy project skeleton to server. Place `public/` as web root.
2. Upload `sql/schema_core.sql` and `setup.php` to project root and make sure `config/` is writable for installer.
3. Visit `/setup.php` in a browser once to run installer (creates DB and `config.php`).
4. Remove or protect `setup.php` after running.
5. Begin building controllers/views: login, dashboard, students CRUD, attendance form.

---

## 15) Extras (UX hints)
- Add in-app notifications for parents when attendance is marked absent.
- Make announcements optionally push to email (queue using cron).
- Provide CSV import for students and teachers to speed enrollment.

---

## 16) If you want I can also:
- Generate the full starter project ZIP (controllers, basic views, CSS, JS) ready to upload.
- Produce a pixel-perfect HTML/CSS prototype for the main pages (dashboard, students list, attendance form).

---

**End of wireframe & installer guide.**


