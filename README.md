<div align="center">

# Primary School Management Application

### A Complete Web-Based School Management System Built with PHP & MySQL

<p align="center">

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-Markup-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-Styling-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap)
![License](https://img.shields.io/badge/License-Educational-blue?style=for-the-badge)

</p>

*A complete web-based Primary School Management System designed to simplify academic and administrative operations.*

</div>

---

# About

The **Primary School Management Application** is a comprehensive web-based management system developed using **PHP**, **MySQL**, **HTML**, **CSS**, **JavaScript**, and **Bootstrap**.

The application automates the daily activities of a primary school, including student management, teacher management, attendance, class routines, examinations, results, fees, and administrative operations.

It provides an easy-to-use interface for administrators, teachers, students, and parents while ensuring secure data management and efficient school administration.

---

# Key Features

## Administrator

- Secure Admin Login
- Dashboard Overview
- Student Management
- Teacher Management
- Class Management
- Subject Management
- Section Management
- Academic Session Management
- Fee Management
- Attendance Management
- Examination Management
- Result Management
- Notice Board
- User Management
- Report Generation
- Backup Database

---

## Student Management

- Student Registration
- Student Profile
- Student Promotion
- Student List
- Student Attendance
- Student Result
- Student Fee Information
- Guardian Information

---

## Teacher Management

- Teacher Registration
- Teacher Profile
- Teacher Attendance
- Subject Assignment
- Class Assignment
- Salary Information

---

## Academic Management

- Academic Year
- Classes
- Sections
- Subjects
- Class Routine
- Exam Routine
- Exam Schedule

---

## Attendance System

- Daily Attendance
- Monthly Attendance
- Attendance Reports
- Teacher Attendance
- Student Attendance

---

## Examination & Results

- Create Exams
- Marks Entry
- Grade Calculation
- Result Generation
- Printable Marksheet
- Result Reports

---

## Fee Management

- Student Fees
- Payment History
- Due List
- Invoice Generation
- Payment Reports

---

## Reports

- Student Reports
- Teacher Reports
- Attendance Reports
- Result Reports
- Fee Reports
- Academic Reports

---

# System Workflow

```text
Administrator Login
        │
        ▼
 Dashboard
        │
        ├───────────────┐
        │               │
        ▼               ▼
Student Module    Teacher Module
        │               │
        ▼               ▼
Admission      Teacher Registration
        │               │
        └───────────────┘
                │
                ▼
        Academic Management
                │
                ▼
      Attendance Management
                │
                ▼
      Examination Management
                │
                ▼
         Result Processing
                │
                ▼
          Fee Management
                │
                ▼
          Report Generation
                │
                ▼
            MySQL Database
```

---

# System Architecture

```text
Users
 │
 ├── Administrator
 ├── Teacher
 ├── Student
 └── Parent
          │
          ▼
     PHP Application
          │
 ┌────────┼───────────┐
 │        │           │
 ▼        ▼           ▼
Authentication  School Modules  Reports
 │        │           │
 └────────┴───────────┘
          │
          ▼
      MySQL Database
```

---

# Technology Stack

| Category | Technology |
|:----------|:-----------|
| Programming Language | PHP |
| Database | MySQL |
| Frontend | HTML5 |
| Styling | CSS3 |
| Framework | Bootstrap |
| Client-side Scripting | JavaScript |
| Web Server | Apache (XAMPP/WAMP) |
| Version Control | Git & GitHub |

---

# Core Modules

| Module | Description |
|:--------|:------------|
| Authentication | Secure Login System |
| Dashboard | Overview of School Activities |
| Student Management | Student Information |
| Teacher Management | Teacher Records |
| Class Management | Classes & Sections |
| Subject Management | Subject Allocation |
| Attendance | Daily Attendance |
| Examination | Exam Management |
| Result | Result Processing |
| Fees | Student Payment System |
| Notice Board | School Announcements |
| Reports | Printable Reports |

---

# Screenshots

Create an **assets/screenshots** folder.

```
assets/

└── screenshots/
    ├── dashboard.png
    ├── login.png
    ├── students.png
    ├── teachers.png
    ├── attendance.png
    ├── examination.png
    ├── results.png
    └── fees.png
```

Example

```md
## Dashboard

<p align="center">
<img src="assets/screenshots/dashboard.png" width="90%">
</p>

## Student Management

<p align="center">
<img src="assets/screenshots/students.png" width="90%">
</p>

## Attendance

<p align="center">
<img src="assets/screenshots/attendance.png" width="90%">
</p>
```
---

# Project Highlights

- Modern Dashboard
- Secure Authentication
- Student Information Management
- Teacher Management
- Attendance Tracking
- Examination System
- Automatic Result Processing
- Fee Collection
- Printable Reports
- Responsive Design
- Easy Administration
- MySQL Database Integration

---

---

# Installation

## Prerequisites

Before running this project, ensure the following software is installed on your system:

- PHP 8.0 or later
- MySQL 5.7 or later
- Apache Web Server
- XAMPP / WAMP / Laragon
- Git (Optional)

---

## Clone the Repository

```bash
git clone https://github.com/your-username/primary-school-management-application.git
```

Move into the project directory.

```bash
cd primary-school-management-application
```

---

## Database Setup

1. Open **phpMyAdmin**
2. Create a new database

```
school_management
```

3. Import the SQL file

```
database/school_management.sql
```

4. Update your database configuration.

```php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "school_management";
```

---

## Run the Project

Place the project inside your web server directory.

Example (XAMPP):

```
htdocs/
    primary-school-management-application/
```

Start:

- Apache
- MySQL

Open your browser.

```
http://localhost/primary-school-management-application
```

---

# Default Login

> Change these credentials after the first login.

| Role | Username | Password |
|:-----|:---------|:---------|
| Administrator | admin | admin123 |
| Teacher | teacher | teacher123 |
| Student | student | student123 |

---

# Usage

### Administrator

- Manage students
- Manage teachers
- Create classes
- Assign subjects
- Manage attendance
- Publish notices
- Create examinations
- Generate reports

---

### Teacher

- Login
- View assigned classes
- Take attendance
- Enter student marks
- View schedules

---

### Student

- Login
- View profile
- View attendance
- Check examination results
- Download report cards

---

### Parent *(Optional Module)*

- View child information
- Attendance records
- Examination results
- Fee payment history

---

# Database Overview

The application uses **MySQL** to securely store academic and administrative information.

| Table | Description |
|:------|:------------|
| admin | Administrator accounts |
| teachers | Teacher information |
| students | Student records |
| parents | Parent information |
| classes | Class information |
| sections | Section details |
| subjects | Subject records |
| attendance | Student attendance |
| examinations | Exam details |
| results | Examination results |
| fees | Student fee records |
| notices | School announcements |

---

# Future Enhancements

The project can be further enhanced with additional features such as:

- Online Admission
- Student ID Card Generator
- Online Examination
- Online Fee Payment
- SMS Notification
- Email Notification
- Parent Portal
- Teacher Payroll
- Library Management
- Hostel Management
- Transport Management
- Certificate Generator
- REST API
- Mobile Application
- QR Code Student Attendance
- Cloud Deployment

---

# Contributing

Contributions are welcome.

1. Fork the repository

2. Create a new feature branch

```bash
git checkout -b feature-name
```

3. Commit your changes

```bash
git commit -m "Add new feature"
```

4. Push the branch

```bash
git push origin feature-name
```

5. Create a Pull Request

---

# License

© 2026 **Md. Ratan Ali**. All Rights Reserved.

This project is a real-world software application developed for production use.

No part of this project, including its source code, design, documentation, or other assets, may be copied, modified, distributed, reproduced, or used without prior written permission from the author.

For licensing, commercial inquiries, collaboration opportunities, or authorized access, please contact the author directly.

---

# Contact

For business inquiries, licensing, project collaboration, or technical discussions, feel free to connect with me.

<div align="center">

### 👨‍💻 Md. Ratan Ali

**Full Stack Web Developer • Graphic Designer • Entrepreneur**

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Connect-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white)](https://linkedin.com/in/mdratanali)

[![GitHub](https://img.shields.io/badge/GitHub-Profile-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/themdratanali)

</div>

---

# Copyright

**© 2026 Md. Ratan Ali. All Rights Reserved.**

Unauthorized copying, distribution, modification, reverse engineering, or commercial use of this software without written permission is strictly prohibited.

---

<div align="center">

## ⭐ Thank You for Visiting ⭐

**Primary School Management Application**

*A Professional School Management Solution Built with PHP & MySQL*

Developed and Maintained by **Md. Ratan Ali**

For business inquiries or collaboration, please connect via **LinkedIn**.

</div>
