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

Healthcare facilities in Tanzania currently rely on manual, paper-based referral letters that are frequently lost, provide no tracking mechanism, and offer no feedback to referring COs. The **Referral Management System (RMS)** solves this by providing a structured, digital referral platform accessible via any standard web browser.

The system supports **three user roles** — COs/Doctors, Hospital Administrators, and Ministry of Health Officials — each with role-based access to specific features. Users are registered directly in the database by the developer during system setup.

---

## Features

- ✅ Modern React.js frontend with single-page application features
- ✅ Secure login with Role-Based Access Control (RBAC)
- ✅ Digital referral creation and submission
- ✅ Real-time referral and service status tracking (refarral status : referral accepted → referral rejected.    service offering status : In Progress → Completed)
- ✅ Bidirectional feedback — receiving facility sends clinical outcomes back to referring CO
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
├── frontend/                          # React.js frontend application
│   ├── public/
│   ├── src/
│   │   ├── components/               # React components
│   │   ├── pages/                    # React pages
│   │   ├── services/                 # API service calls
│   │   └── App.js
│   ├── package.json
│   └── package-lock.json
│
├── backend/                          # PHP backend API
│   ├── config/
│   │   └── db.php                    # Database connection
│   ├── includes/
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── navbar.php
│   │   └── session.php               # Session & auth check
│   ├── modules/
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   └── logout.php
│   │   ├── referrals/
│   │   │   ├── create_referral.php
│   │   │   ├── view_referrals.php
│   │   │   ├── update_status.php
│   │   │   └── referral_details.php
│   │   ├── feedback/
│   │   │   └── send_feedback.php
│   │   ├── notifications/
│   │   │   └── notify.php
│   │   ├── dashboard/
│   │   │   ├── co_dashboard.php
│   │   │   ├── admin_dashboard.php
│   │   │   └── moh_dashboard.php
│   │   └── facilities/
│   │       └── manage_facilities.php
│   ├── database/
│   │   └── rms_database.sql          # Full database schema + seed data
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── index.php                     # Backend API entry point
│
├── .gitignore
└── README.md
```

> **Frontend & Backend Integration:** The React frontend (port 3000) communicates with the PHP backend API via HTTP requests. Ensure both are running simultaneously during development.

---

## Installation & Setup

Follow these steps carefully after cloning the repository:

**Step 1 — Clone the repository**

```bash
git clone https://github.com/your-username/rms.git
cd rms
```

**Step 2 — Backend Setup (PHP/MySQL)**

Move the entire project to your XAMPP `htdocs` directory:

```
C:/xampp/htdocs/rms        (Windows)
/opt/lampp/htdocs/rms      (Linux)
```

**Step 3 — Start XAMPP Services**

- Open XAMPP Control Panel
- Start **Apache** and **MySQL**

**Step 4 — Configure database connection**

Open `backend/config/db.php` and update with your local credentials:

```php
<?php
$host     = "localhost";
$dbname   = "health_db";
$username = "root";
$password = "";

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

**Step 5 — Import database schema**

- Open your browser and go to `http://localhost/phpmyadmin`
- Create a new database named `health_db`
- Click **Import** → select the file `backend/database/rms_database.sql`
- Click **Go**

**Step 6 — Frontend Setup (React)**

Navigate to the frontend directory and install dependencies:

```bash
cd frontend
npm install
```

**Step 7 — Configure API endpoint (Frontend)**

In your React service files (`frontend/src/services/`), set the backend API URL:

```javascript
const API_URL = "http://localhost/rms/backend";
```

**Step 8 — Start the React development server**

From the `frontend` directory:

```bash
npm start
```

The React app will open on `http://localhost:3000`

**Step 9 — Access the system**

- Frontend (React): `http://localhost:3000`
- Backend API: `http://localhost/rms/backend`
- PHPMyAdmin: `http://localhost/phpmyadmin`

> **Note:** Both frontend and backend must be running simultaneously. Frontend on port 3000 (React dev server), backend on localhost via Apache.

---

## API Testing Guide

After completing Phase 3, you can test the API endpoints using tools like **Postman** or **curl**. Here are the basic endpoints we've built:

### 1. Test API Connection
```bash
curl http://localhost/rms/backend/
```

### 2. Login Test
```bash
curl -X POST http://localhost/rms/backend/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"co@rms.go.tz","password":"co123"}'
```

### 3. Get Facilities (requires login session)
```bash
curl http://localhost/rms/backend/facilities/manage_facilities.php
```

### 4. Logout
```bash
curl -X POST http://localhost/rms/backend/auth/logout.php
```

**Expected Responses:**
- Successful login: `{"success":true,"user":{...}}`
- Facilities list: `{"success":true,"facilities":[...],"count":9}`
- Authentication error: `{"error":"Authentication required"}`

---

## Database Setup

The file `backend/database/rms_database.sql` contains:

- All table definitions with proper relationships
- Seed data with test users, facilities, and sample referrals
- Indexes for performance optimization

### Core Tables

| Table | Description | Key Fields |
|---|---|---|
| `users` | All system users with roles | id, email, password, role (co/admin/moh), facility_id |
| `facilities` | Health facilities (5 tiers) | id, name, tier, region, district, capacity |
| `patients` | Patient basic information | id, first_name, last_name, date_of_birth, gender |
| `referrals` | Referral records & status | id, patient_id, status (pending/accepted/in_progress/completed/rejected), urgency |
| `feedback` | Clinical feedback from receiving facilities | id, referral_id, clinical_outcome, treatment_given |
| `notifications` | Email/SMS notification logs | id, referral_id, type (email/sms), status (sent/failed/pending) |

### Tanzania Health System Tiers

The facilities table supports all 5 tiers:
- **Dispensary** (Primary level)
- **Health Centre** (Primary level)
- **District Hospital** (Secondary level)
- **Regional Hospital** (Tertiary level)
- **National Hospital** (Tertiary level)

### Referral Status Workflow

```
pending → accepted → in_progress → completed
    ↓
 rejected
```

### Seed Data Included

- **9 sample facilities** across different tiers and regions
- **3 test users** (one per role: CO, Admin, MOH)
- **3 sample patients**
- **1 sample referral** with feedback and notifications

---

## Default Login Credentials

These accounts are seeded in the database for testing. **Change passwords before any real deployment.**

| Role | Email | Password |
|---|---|---|
| CO / Doctor | co@rms.go.tz | co123 |
| Hospital Admin | admin@rms.go.tz | admin123 |
| MoH Official | moh@rms.go.tz | moh123 |

> **Security Note:** Passwords are stored in plain text for development/testing. In production, implement proper password hashing using PHP's `password_hash()` and `password_verify()`. All users are registered directly in the database by the developer. There is no public self-registration feature in this system.

---

## User Roles & Permissions

### CO / Doctor
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

**API Endpoints:**
- `POST /auth/login.php` — User login with email/password
- `POST /auth/logout.php` — User logout and session destruction

### 2. Referral Creation Module
COs fill out a digital referral form capturing:
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
After a referral is completed, the receiving facility can send a feedback report — including clinical outcome, treatment given, and discharge summary — back to the referring CO.

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

**API Endpoints:**
- `GET /facilities/manage_facilities.php` — Get all facilities (requires authentication)

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
CO creates referral
         ↓
    Status: PENDING
         ↓
Hospital Admin reviews referral
         ↓
   ┌─────┴─────┐
ACCEPTED     REJECTED
   ↓               ↓
Status:        CO
IN PROGRESS    notified
   ↓
Patient treated at receiving facility
   ↓
Feedback sent back to referring CO
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
