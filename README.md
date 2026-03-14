# ⏱️ TimeGrid — Smart Academic Timetable Management System

> **100% Conflict-Free. Auto-Generated. Print-Ready.**  
> A complete school/college timetable management system built with PHP & MySQL.

---

## 📌 Table of Contents

- [Overview](#overview)
- [Live Demo](#live-demo)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Project Structure](#project-structure)
- [Wizard Walkthrough](#wizard-walkthrough)
- [Routine Viewer Modes](#routine-viewer-modes)
- [Demo Data Loader](#demo-data-loader)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

**TimeGrid** is a full-featured, multi-tenant academic timetable management system. It handles everything from initial school configuration to conflict-free timetable generation, daily substitution management, workload analysis, and beautiful print-ready PDF exports.

Designed for school administrators, TimeGrid drastically reduces the time spent on manual timetable creation — from hours to seconds.

---

## Live Demo

> 🌐 Contact [OfferPlant Technologies](https://wa.me/919431426600) for a live demo walkthrough.

---

## ✨ Features

### 🔐 Authentication & Multi-Tenancy
- Secure **organisation-based multi-tenant** system — each school has isolated data
- **Registration** with 14-day free trial and duplicate email detection
- **Login** with hashed passwords (`password_hash`)
- **Forgot Password** with secure token-based reset flow
- Session-aware landing page — logged-in users see "Go to Dashboard" instead of Login/Register

### 🧙 Setup Wizard (6 Steps)
| Step | Purpose |
|---|---|
| **Step 1** | Configure academic week (days, periods, lunch break, Saturday half-day) |
| **Step 2** | Manage Classes & Subjects with full CRUD |
| **Step 3** | Subject Mapping — assign subjects per class |
| **Step 4** | Add Teachers, assign subjects & classes |
| **Step 5** | Teacher Availability & Restrictions (block specific periods) |
| **Step 6** | Auto-generate timetable with conflict detection |

### 🗂️ Manage Classes
- Add/Edit/Delete classes (e.g., Class V-A, Class X-B)
- Visual subject count badge per class (green = mapped, red = unmapped)
- **One-click "Load Demo Classes (V–X)"** — instantly populates 12 standard school sections

### 📚 Manage Subjects
- Full CRUD with subject color coding and priority tiers
- Priority levels control scheduling order (P1 = first period, P5 = last)
- **One-click "Load CBSE Subjects"** — 15 standard CBSE subjects with colors & priorities

### 🔗 Subject Mapping
- Class-wise subject assignment with checkbox UI
- **Auto-Map All Subjects → All Classes** with one click
- Live badge updates as subjects are checked

### 👨‍🏫 Teacher Management
- Add teachers with: name, employee code, weekly class limit, min leisure per day, max unique subjects
- Assign teachers to specific subjects × classes
- Set Class Teacher designation per class
- Intelligent subject dropdown filtered by class-subject mapping
- **"Load Demo Teachers + Auto-Assign"** — adds 15 CBSE-aligned teachers, assigns them to all subjects across all classes automatically

### 🚫 Teacher Availability & Restrictions
- Block specific teachers from specific periods on specific days
- Ensures restrictions are respected during generation

### ⚡ Timetable Generation Engine
- Constraint-based greedy algorithm with backtracking
- Handles: lunch breaks, Saturday half-days, max continuous periods, class teacher in 1st period
- Supports **Different daily schedule** or **Uniform (same every day)** modes
- Real-time generation log with conflict reporting
- **Fast Re-generate** button from dashboard

### 📅 Routine Viewer — 4 Display Modes
| Mode | Description |
|---|---|
| **Day-wise** | All classes × all periods for a selected day |
| **Class-wise** | Full weekly schedule for a specific class |
| **Teacher-wise** | Full weekly schedule for a specific teacher |
| **Period-wise** | All classes across all days for a specific period |

- TODAY indicator on current day
- Proxy/Absent/Duty badges for live substitutions
- Tabbed navigation between modes
- Dynamic filters (day pills, period pills, dropdowns)

### 📋 Master Timetable (Full Routine)
- Full school timetable for a selected day in a grid layout
- All classes as columns, all periods as rows
- Lunch break row auto-inserted
- Export PDF per day

### 🖨️ Print & Export
- Beautiful print-ready HTML routine per day
- PDF export per working day, direct from dashboard quick-links
- Printer-optimized CSS

### 🔄 Daily Substitution Center
- Select absent teacher + date
- Shows their period-wise schedule for that day
- Assign proxy teacher (only free teachers shown per period)
- Remove or update substitution
- Alert badge on dashboard when active absences exist

### 📊 System Analysis Report
- Teacher-wise workload breakdown (periods per day/week)
- Coverage percentage per class
- Subject distribution across teachers
- Unassigned slot detection

### ⚙️ Settings
- Modify working days, period counts, lunch break position
- Saturday half-day period limits
- Generation constraints (max continuous, class teacher rule)
- All settings are org-specific

### 🎨 Premium Dashboard
- Live stats: Faculty count, Classes, Subjects, Coverage %
- Progress bar for slot coverage
- All routine viewer modes as quick-link cards
- Per-day PDF export shortcuts for all working days
- Live status sidebar: today's day, absences, routine config summary
- Absence alert with link to substitution center

### 🚀 Demo Data Loader (for new users)
Full one-click demo setup across the wizard:
1. **Load Demo Classes** → Class V-A through X-B (12 sections)
2. **Load CBSE Subjects** → 15 subjects with colors & priorities
3. **Auto-Map All** → Every subject mapped to every class instantly
4. **Load Demo Teachers** → 15 named teachers with CBSE subject assignments + class teacher roles

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.x (procedural + OOP hybrid) |
| **Database** | MySQL 8 / MariaDB |
| **Frontend** | Vanilla HTML5, CSS3, JavaScript (ES6) |
| **Icons** | Font Awesome 6 |
| **Fonts** | Google Fonts (Outfit, Plus Jakarta Sans) |
| **Server** | Apache (XAMPP / cPanel / any PHP host) |
| **Auth** | PHP Sessions + `password_hash` / `password_verify` |

---

## ⚙️ Installation

### Requirements
- PHP 7.4+ (recommended: PHP 8.x)
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite` enabled

### Steps

```bash
# 1. Clone into your web root
git clone https://github.com/krsaurabhmca/timetable.git
cd timetable

# 2. Import the database
# Open phpMyAdmin or run:
mysql -u root -p < timetable_db.sql

# 3. Configure database connection
nano config.php
```

Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'timetable_db');
define('BASE_URL', 'http://localhost/timetable');
```

```bash
# 4. Open in browser
http://localhost/timetable/
```

---

## 🗄️ Database Setup

Import `timetable_db.sql` — it contains:

| Table | Purpose |
|---|---|
| `organizations` | Multi-tenant org registry with trial dates |
| `users` | Admin users linked to orgs |
| `settings` | Per-org key-value config |
| `classes` | School sections |
| `subjects` | Subject registry with color & priority |
| `class_subjects` | Class ↔ Subject mapping |
| `teachers` | Teacher profiles with limits & class teacher role |
| `teacher_assignments` | Teacher ↔ Subject ↔ Class mapping |
| `teacher_restrictions` | Blocked period/day per teacher |
| `timetable` | Generated timetable entries |
| `timetable_adjustments` | Daily substitution records |
| `attendance_logs` | Teacher absence tracking |

---

## 📁 Project Structure

```
timetable/
├── index.php                  # Landing page (session-aware)
├── login.php                  # Login
├── register.php               # Registration with duplicate email guard
├── forgot_password.php        # Password reset request
├── reset_password.php         # Token-based reset
├── logout.php                 # Session destroy
├── dashboard.php              # Main dashboard with quick links
├── config.php                 # DB connection & helpers
├── migrate.php                # DB migration runner
│
├── wizard/
│   ├── step1.php              # General settings
│   ├── step2.php              # Classes & Subjects management
│   ├── step2_mapping.php      # Subject-class mapping
│   ├── step3.php              # Teacher management & assignments
│   ├── step4.php              # Teacher restrictions
│   └── step5.php             # Timetable generation
│
├── view_timetable.php         # 4-mode routine viewer
├── full_routine.php           # Master timetable (day-wise grid)
├── print_routine.php          # Print/PDF export
├── adjustments.php            # Daily substitution center
├── analysis_report.php        # Workload & coverage analysis
├── settings.php               # Org settings editor
│
├── api/
│   ├── demo_data.php          # Demo data loader (4 actions)
│   └── mock_data.php          # Legacy mock loader
│
├── includes/
│   ├── header.php             # Nav + HTML head
│   ├── footer.php             # Closing HTML
│   └── session.php            # Auth guard
│
├── assets/
│   └── css/style.css          # Global stylesheet
│
└── timetable_db.sql           # Full database schema + seed
```

---

## 🧙 Wizard Walkthrough

```
Step 1 → Configure Week
         (Mon–Sat, 8 periods/day, 45min, lunch after P4)
         ↓
Step 2 → Add Classes & Subjects
         [Load Demo] → Classes V–X + 15 CBSE Subjects
         ↓
Step 3 → Map Subjects to Classes
         [Auto-Map All] → All subjects to all classes in 1 click
         ↓
Step 4 → Add Teachers + Assign Subjects
         [Load Demo Teachers] → 15 teachers auto-assigned to subjects
         ↓
Step 5 → Set Restrictions (optional)
         Block teachers from specific periods/days
         ↓
Step 6 → Generate Timetable ⚡
         Algorithm runs, conflicts reported, timetable saved
```

---

## 📅 Routine Viewer Modes

Access via **Dashboard → Routine Viewer** or directly at `view_timetable.php`

| URL Parameter | Mode |
|---|---|
| `?view=day&day=Monday` | Day-wise view |
| `?view=class&class_id=3` | Class-wise view |
| `?view=teacher&teacher_id=5` | Teacher-wise view |
| `?view=period&period=1` | Period-wise view |

---

## 🎭 Demo Data Loader

For quick demos, use the **one-click loaders** in each wizard step:

| Button Location | Action | What it Creates |
|---|---|---|
| Step 2 → Classes | Load Demo Classes (V–X) | 12 class sections (V-A to X-B) |
| Step 2 → Subjects | Load CBSE Subjects | 15 subjects with colors & priorities |
| Step 3 → Mapping | Auto-Map All | Assigns every subject to every class |
| Step 4 → Teachers | Load Demo Teachers | 15 teachers + auto subject assignments |

> ⚡ After running all 4 loaders → go to Step 6 and hit **Generate** to see a complete working timetable in under 60 seconds.

---

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first.

1. Fork the repo
2. Create your branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'feat: add my feature'`
4. Push: `git push origin feature/my-feature`
5. Open a Pull Request

---

## 📞 Support

- 💬 WhatsApp: [+91 9431426600](https://wa.me/919431426600)
- 🏢 By [OfferPlant Technologies Pvt. Ltd.](https://offerplant.com)

---

## 📄 License

This project is proprietary software owned by **OfferPlant Technologies Pvt. Ltd.**  
Unauthorized redistribution or resale is prohibited.

© 2025 TimeGrid · OfferPlant Technologies
