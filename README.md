# Referral Management System (RMS)

> A web-based platform that digitalises and streamlines the patient referral process across all five tiers of Tanzania's public healthcare system.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [System Requirements](#system-requirements)
- [Project Structure](#project-structure)
- [Installation & Setup](#installation--setup)
- [Database Setup](#database-setup)
- [Default Login Credentials](#default-login-credentials)
- [User Roles & Permissions](#user-roles--permissions)
- [System Modules](#system-modules)
- [Health System Coverage](#health-system-coverage)
- [Referral Status Workflow](#referral-status-workflow)
- [Notification System](#notification-system)
- [Out of Scope](#out-of-scope)
- [Project Info](#project-info)

---

## Project Overview

Healthcare facilities in Tanzania currently rely on manual, paper-based referral letters that are frequently lost, provide no tracking mechanism, and offer no feedback to referring clinicians. The **Referral Management System (RMS)** solves this by providing a structured, digital referral platform accessible via any standard web browser.

The system supports **three user roles** — Clinicians/Doctors, Hospital Administrators, and Ministry of Health Officials — each with role-based access to specific features. Users are registered directly in the database by the developer during system setup.

---

## Features

- ✅ Modern React.js frontend with single-page application features
- ✅ Secure login with Role-Based Access Control (RBAC)
- ✅ Digital referral creation and submission
- ✅ Real-time referral and service status tracking (refarral status : referral accepted → referral rejected.    service offering status : In Progress → Completed)
- ✅ Bidirectional feedback — receiving facility sends clinical outcomes back to referring clinician
- ✅ Automated Email & SMS notifications at every referral stage
- ✅ Ministry of Health reporting and analytics dashboard
- ✅ Facility and patient record management
- ✅ Covers all 5 tiers of Tanzania's public health system

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | React.js, HTML5, CSS3, Bootstrap 5, JavaScript |
| Backend | PHP (Procedural) |
| Database | MySQL |
| Email Notifications | PHPMailer |
| SMS Notifications | Africa's Talking API |
| Local Development | XAMPP (Apache + MySQL + PHP) |
| Version Control | Git & GitHub |

---

## System Requirements

Before you begin, make sure you have the following installed:

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- PHP >= 7.4
- MySQL >= 5.7
- [Node.js](https://nodejs.org/) >= 16.0 (for React.js frontend)
- npm (comes with Node.js)
- A web browser (Chrome, Firefox, Edge)
- Git

---

## Project Structure

```
rms/
├── frontend/                   # React.js frontend application
│   ├── public/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── services/
│   │   └── App.js
│   ├── package.json
│   └── README.md
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── config/
│   └── db.php                  # Database connection
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php
│   └── session.php             # Session & auth check
├── modules/
│   ├── auth/
│   │   ├── login.php
│   │   └── logout.php
│   ├── referrals/
│   │   ├── create_referral.php
│   │   ├── view_referrals.php
│   │   ├── update_status.php
│   │   └── referral_details.php
│   ├── feedback/
│   │   └── send_feedback.php
│   ├── notifications/
│   │   └── notify.php
│   ├── dashboard/
│   │   ├── clinician_dashboard.php
│   │   ├── admin_dashboard.php
│   │   └── moh_dashboard.php
│   └── facilities/
│       └── manage_facilities.php
├── database/
│   └── rms_database.sql        # Full database schema + seed data
├── .env.example                # Environment variables template
├── .gitignore
└── README.md
```

---

## Installation & Setup

Follow these steps carefully after cloning the repository:

**Step 1 — Clone the repository**

```bash
git clone https://github.com/your-username/rms.git
cd rms
```

**Step 2 — Move project to XAMPP**

Copy or move the project folder into your XAMPP `htdocs` directory:

```
C:/xampp/htdocs/rms        (Windows)
/opt/lampp/htdocs/rms      (Linux)
```

**Step 3 — Start XAMPP**

- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

**Step 4 — Configure database connection**

Open `config/db.php` and update with your local credentials:

```php
<?php
$host     = "localhost";
$dbname   = "rms_db";
$username = "root";
$password = "";

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

**Step 5 — Run the database**

- Open your browser and go to `http://localhost/phpmyadmin`
- Create a new database named `rms_db`
- Click **Import** → select the file `database/rms_database.sql`
- Click **Go**

**Step 6 — Install React dependencies**

Navigate to the frontend directory and install dependencies:

```bash
cd frontend
npm install
```

**Step 7 — Build React application**

Build the React frontend for production:

```bash
npm run build
```

**Step 8 — Launch the system**

Open your browser and go to:

```
http://localhost/rms/index.php          # Landing page
http://localhost/rms/modules/auth/login.php  # Login page
```

> **Note:** For development, you can run `npm start` in the frontend directory to start the React development server on `http://localhost:3000`

---

## Database Setup

The file `database/rms_database.sql` contains:

- All table definitions (users, facilities, patients, referrals, feedback, notifications)
- Seed data with one test user per role for immediate testing

### Core Tables

| Table | Description |
|---|---|
| `users` | All system users with roles |
| `facilities` | Health facilities (dispensaries to national hospitals) |
| `patients` | Patient basic information |
| `referrals` | All referral records and status |
| `feedback` | Clinical feedback sent by receiving facilities |
| `notifications` | Email/SMS notification logs |

---

## Default Login Credentials

These accounts are seeded in the database for testing. **Change passwords before any real deployment.**

| Role | Email | Password |
|---|---|---|
| Clinician / Doctor | clinician@rms.go.tz | clinician123 |
| Hospital Admin | admin@rms.go.tz | admin123 |
| MoH Official | moh@rms.go.tz | moh123 |

> **Note:** All users are registered directly in the database by the developer. There is no public self-registration feature in this system.

---

## User Roles & Permissions

### Clinician / Doctor
- Create and submit patient referrals
- Track the real-time status of their referrals
- Receive and view clinical feedback from receiving facilities
- View patient referral history

### Hospital Administrator
- Accept or reject incoming referrals
- Update referral status (In Progress / Completed)
- Send clinical feedback and counter-referrals
- Manage facility users and records
- View all referrals linked to their facility

### Ministry of Health Official
- View national referral reports and analytics
- Monitor referral volumes and trends
- View facility-level performance metrics
- Access the MoH reporting dashboard

---

## System Modules

### 1. Authentication Module
Handles secure login and session management. Each user is redirected to their role-specific dashboard after login. Unauthorized access to other role pages is blocked via session checks.

### 2. Referral Creation Module
Clinicians fill out a digital referral form capturing:
- Patient name, age, gender
- Clinical reason for referral
- Urgency level (Emergency / Urgent / Routine)
- Originating facility and destination facility

### 3. Referral Status Tracking Module
All stakeholders can monitor the live status of any referral:

```
Pending → Accepted → In Progress → Completed
```

### 4. Bidirectional Feedback Module
After a referral is completed, the receiving facility can send a feedback report — including clinical outcome, treatment given, and discharge summary — back to the referring clinician.

### 5. Notification Module
Automatic notifications are sent via **Email (PHPMailer)** and **SMS (Africa's Talking)** when:
- A referral is submitted
- A referral is accepted or rejected
- A referral status is updated
- Clinical feedback is sent

### 6. MoH Dashboard Module
The MoH Official dashboard displays:
- Total referrals by facility, region, and period
- Common referral diagnoses
- Referral acceptance and completion rates
- Facility performance comparison

### 7. Facility Management Module
Hospital Admins can view and manage their facility profile, patient records, and referral history.

---

## Health System Coverage

The RMS covers all five tiers of Tanzania's public health system:

```
Dispensary
     ↓
Health Centre
     ↓
District Hospital
     ↓
Regional Hospital
     ↓
National / Referral Hospital
```

Referrals flow upward through these tiers. Feedback flows back downward to the referring facility.

---

## Referral Status Workflow

```
Clinician creates referral
         ↓
    Status: PENDING
         ↓
Hospital Admin reviews referral
         ↓
   ┌─────┴─────┐
ACCEPTED     REJECTED
   ↓               ↓
Status:        Clinician
IN PROGRESS    notified
   ↓
Patient treated at receiving facility
   ↓
Feedback sent back to referring clinician
   ↓
Status: COMPLETED
```

---

## Notification System

| Event | Email | SMS |
|---|---|---|
| Referral submitted | ✅ | ✅ |
| Referral accepted | ✅ | ✅ |
| Referral rejected | ✅ | ✅ |
| Status updated | ✅ | ✅ |
| Feedback received | ✅ | ✅ |

Configure your email and SMS credentials in `config/db.php` or a `.env` file before testing notifications.

---

## Out of Scope

The following are **not included** in this system:

- ❌ AI or Machine Learning features
- ❌ Mobile application (Android / iOS)
- ❌ NHIF or insurance platform integration
- ❌ Electronic Health Records (EHR)
- ❌ Laboratory or pharmacy management
- ❌ Telemedicine or video consultation
- ❌ Hardware or network infrastructure

---

## Project Info

| Field | Details |
|---|---|
| **Project Title** | Referral Management System (RMS) |
| **Project Type** | Software Development |
| **Student** | Abdulkarim Macho Rwenda |
| **Registration No.** | NIT/BIT/2023/2223 |
| **Program** | BSc Information Technology |
| **Institution** | National Institute of Transport (NIT), Dar es Salaam |
| **Supervisor** | Mr. Said Chang'a |
| **Academic Year** | 2025 / 2026 |

---

> *"Improving Tanzania's Health Referral Chain — One Facility at a Time."*
