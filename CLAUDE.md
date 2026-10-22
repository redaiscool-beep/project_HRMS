# project_HRMS

## Stack
- PHP
- PostgreSQL
- HTML/CSS/JavaScript
- PDO
- Session Authentication
- CSRF Protection
- Development Port: 8001

## Goal
Mini HRMS สำหรับจัดการข้อมูลพนักงานและประวัติที่เกี่ยวข้อง
เน้น Personnel Management และการใช้ฐานข้อมูลร่วมกัน
ไม่รวม Payroll และ Time Attendance แบบเต็มระบบ

## Main Modules
1. Dashboard
2. Department
3. Position
4. Employee Type
5. Employee
6. Education
7. Work History
8. Movement
9. Training
10. Certificate / Skill
11. Leave
12. Reports

## Core Tables
- hr_department
- hr_position
- hr_employee_type
- hr_employee
- hr_employee_education
- hr_employee_work_history
- hr_employee_movement
- hr_employee_training
- hr_employee_certificate
- hr_employee_skill
- hr_leave_type
- hr_leave_request

## Main Relationships
- hr_employee.department_id -> hr_department.department_id
- hr_employee.position_id -> hr_position.position_id
- hr_employee.employee_type_id -> hr_employee_type.employee_type_id

Employee 1:N
- Education
- Work History
- Movement
- Training
- Certificate
- Skill
- Leave

## Important Design Rule
- hr_employee = ข้อมูลสถานะปัจจุบันของพนักงาน
- hr_employee_movement = ประวัติการเปลี่ยนแปลงย้อนหลัง

### Transfer
1. INSERT hr_employee_movement
2. UPDATE hr_employee.department_id

### Position Change / Promotion
1. INSERT hr_employee_movement
2. UPDATE hr_employee.position_id

### Resignation
1. INSERT hr_employee_movement
2. UPDATE hr_employee.employment_status = 'RESIGNED'
3. UPDATE resign_date

## Master Data

### Department
Table: hr_department

Main fields:
- department_id
- department_code
- department_name_th
- department_name_en
- parent_department_id
- sort_order
- is_active
- remark
- created_at
- updated_at

Required:
- department_code
- department_name_th

### Position
Table: hr_position

Main fields:
- position_id
- position_code
- position_name_th
- position_name_en
- position_level
- sort_order
- is_active
- remark
- created_at
- updated_at

Required:
- position_code
- position_name_th

### Employee Type
Table: hr_employee_type

Main fields:
- employee_type_id
- employee_type_code
- employee_type_name
- is_active
- remark
- created_at
- updated_at

## Employee

Table: hr_employee

Main fields:
- employee_id
- employee_code
- prefix
- first_name
- last_name
- first_name_en
- last_name_en
- gender
- birth_date
- national_id
- email
- phone
- address
- department_id
- position_id
- employee_type_id
- hire_date
- probation_end_date
- resign_date
- employment_status
- profile_image
- remark
- created_at
- updated_at

Required:
- employee_code
- first_name
- last_name
- department_id
- position_id
- employee_type_id
- hire_date
- employment_status

Employment Status:
- ACTIVE
- PROBATION
- RESIGNED
- INACTIVE

Employee Code ต้อง Unique

## Employee Detail Tabs
- General
- Education
- Work History
- Movement
- Training
- Certificate / Skill
- Leave

## Education
Table: hr_employee_education

Fields:
- education_id
- employee_id
- education_level
- degree_name
- major_name
- faculty_name
- institute_name
- start_year
- graduation_year
- gpa
- remark
- created_at
- updated_at

Relationship:
hr_employee 1:N hr_employee_education

## Work History
Table: hr_employee_work_history

Fields:
- work_history_id
- employee_id
- company_name
- position_name
- start_date
- end_date
- salary
- job_description
- reason_for_leaving
- remark
- created_at
- updated_at

## Movement
Table: hr_employee_movement

Movement Types:
- HIRE
- PASS_PROBATION
- TRANSFER
- POSITION_CHANGE
- PROMOTION
- STATUS_CHANGE
- RESIGN

Fields:
- movement_id
- employee_id
- movement_type
- effective_date
- old_department_id
- new_department_id
- old_position_id
- new_position_id
- old_employee_type_id
- new_employee_type_id
- old_status
- new_status
- remark
- created_by
- created_at
- updated_at

## Training
Table: hr_employee_training

Fields:
- training_id
- employee_id
- course_name
- training_provider
- training_type
- start_date
- end_date
- duration
- duration_unit
- certificate_received
- remark
- created_at
- updated_at

duration_unit:
- HOUR
- DAY
- MONTH

## Certificate
Table: hr_employee_certificate

Fields:
- certificate_id
- employee_id
- certificate_name
- certificate_no
- issued_by
- issue_date
- expire_date
- certificate_file
- remark
- created_at
- updated_at

## Skill
Table: hr_employee_skill

Fields:
- skill_id
- employee_id
- skill_name
- skill_level
- experience_year
- remark
- created_at
- updated_at

Skill Level:
- BEGINNER
- INTERMEDIATE
- ADVANCED
- EXPERT

## Leave

### Leave Type
Table: hr_leave_type

Fields:
- leave_type_id
- leave_type_code
- leave_type_name
- max_days
- is_active
- created_at
- updated_at

Initial Types:
- ลาป่วย
- ลากิจ
- ลาพักผ่อน

### Leave Request
Table: hr_leave_request

Fields:
- leave_request_id
- employee_id
- leave_type_id
- start_date
- end_date
- total_days
- reason
- document_status
- approved_by
- approved_at
- remark
- created_at
- updated_at

Document Status:
- DRAFT
- SUBMITTED
- APPROVED
- REJECTED
- CANCELLED

## Dashboard
Dashboard ใช้ Query จากข้อมูลจริง ไม่สร้าง table summary แยกโดยไม่จำเป็น

แสดงอย่างน้อย:
- จำนวนพนักงานทั้งหมด
- จำนวน Active
- จำนวน Probation
- เข้าใหม่เดือนนี้
- ลาออกเดือนนี้
- พนักงานแยกตาม Department
- พนักงานแยกตาม Position
- Movement ล่าสุด
- HR Alerts

## Key Features

### Employee Timeline
สร้างจาก hr_employee_movement
ไม่ต้องสร้าง table ใหม่

### Profile Completeness
คำนวณจาก:
- Employee General
- Education
- Work History
- Certificate
- Skill

ไม่จำเป็นต้องเก็บ percent ลง database

### HR Alert
ตรวจสอบ:
- Probation ใกล้ครบกำหนด
- Certificate ใกล้หมดอายุ
- ไม่มี Education
- Profile ไม่ครบ
- ไม่มี Department
- ไม่มี Position

## Reports

### Employee Report
Filter:
- Department
- Position
- Employee Type
- Status
- Hire Date

Columns:
- Employee Code
- Name
- Department
- Position
- Employee Type
- Hire Date
- Status

### Movement Report
Filter:
- Effective Date
- Movement Type
- Department
- Employee

### Training Report
Filter:
- Employee
- Course
- Date Range
- Department

## Authentication
User Table:
- train_users

Fields:
- id
- username
- password_hash
- role
- is_active
- created_at
- updated_at

Use:
- password_hash()
- password_verify()
- session_regenerate_id(true)

Protected pages:
- requireLogin()

POST forms:
- checkCsrf()

## Security Rules
- Session Authentication
- CSRF Protection
- PDO Prepared Statement
- Password Hashing
- Input Validation
- Escape Output
- ห้ามต่อ SQL จาก user input โดยตรง
- Foreign Key ต้องถูกต้อง

## Delete Strategy
Master Data ไม่ Hard Delete หากมีข้อมูลอ้างอิง
ใช้:
- is_active = true/false

## Audit
Main tables:
- created_at
- updated_at

Important transactions:
- created_by
- updated_by เมื่อเหมาะสม

## UI Pattern

### Main Layout
- Header
- Sidebar
- Breadcrumb
- Page Content

### Sidebar
Dashboard

Master Data
- Department
- Position
- Employee Type
- Leave Type

Employee
- Employee List
- Movement
- Training
- Leave

Reports
- Employee Report
- Movement Report
- Training Report

### List Page
ทุก List Page ควรมี:
- Page Title
- Description
- Add Button
- Search
- Filter
- Table
- Pagination
- View/Edit Action

### Form
- Breadcrumb
- แบ่งเป็น Sections
- Required Validation
- Cancel Button
- Save Button

### Pagination
รองรับ:
- 10 rows/page
- 20 rows/page
- 50 rows/page

## Coding Rules
- PHP ต้องใช้ `declare(strict_types=1);`
- ใช้ `require_once`
- ใช้ PostgreSQL
- ใช้ PDO
- ใช้ Prepared Statement
- ใช้ไฟล์เชื่อมต่อกลาง `includes/db.php`
- ใช้ `db()` สำหรับ database connection
- แยก reusable functions ไว้ใน includes
- หลีกเลี่ยง SQL ซ้ำ
- Validation ต้องทำฝั่ง server
- UI ทุกหน้าควรใช้ layout กลางร่วมกัน

## Suggested Folder Structure

project_HRMS/
- public/
  - index.php
  - login.php
  - logout.php
  - dashboard.php
  - departments.php
  - department_form.php
  - positions.php
  - position_form.php
  - employee_types.php
  - employee_type_form.php
  - employees.php
  - employee_form.php
  - employee_detail.php
  - employee_education.php
  - employee_education_form.php
  - employee_work_history.php
  - employee_work_history_form.php
  - employee_movements.php
  - employee_movement_form.php
  - employee_training.php
  - employee_training_form.php
  - employee_certificates.php
  - employee_certificate_form.php
  - employee_skills.php
  - employee_skill_form.php
  - leave_requests.php
  - leave_request_form.php
  - reports.php
- includes/
  - auth.php
  - db.php
  - csrf.php
  - functions.php
  - header.php
  - sidebar.php
  - footer.php
- assets/
  - css/style.css
  - js/app.js
  - images/
- sql/
  - schema.sql
  - mock_data.sql
- CLAUDE.md
- README.md

## Business Rules
1. Employee Code ห้ามซ้ำ
2. Department Code ห้ามซ้ำ
3. Position Code ห้ามซ้ำ
4. Employee ต้องมี Department
5. Employee ต้องมี Position
6. Employee ต้องมี Employee Type
7. Education/Movement/Training/Certificate/Skill/Leave ต้องอ้างอิง Employee ที่มีอยู่จริง
8. Master Data ที่ถูกใช้งานไม่ Hard Delete
9. Movement ต้องเก็บประวัติย้อนหลัง
10. Employee เก็บข้อมูลปัจจุบัน
11. Transfer ต้อง update Current Department
12. Position Change/Promotion ต้อง update Current Position
13. Resignation ต้อง update Current Status และ Resign Date

## Development Order
1. Database Schema
2. Authentication
3. Master Data
4. Employee
5. Employee Sub Data
6. Movement
7. Leave
8. Dashboard
9. Reports

## Initial Mock Data
- Department: 5
- Position: 10
- Employee Type: 4
- Employee: 20-30
- Education: 30
- Movement: 40
- Training: 20
- Certificate: 15
- Leave: 20

## Out of Scope
Version แรกยังไม่ทำ:
- Payroll
- Tax
- Social Security
- OT
- Time Attendance
- Shift Management
- Recruitment
- Performance Evaluation แบบเต็มระบบ
- ESS แบบเต็มระบบ

## Expected Main Flow
Login
-> Dashboard
-> Master Data
-> Add Employee
-> Employee Profile
-> Education / Work History
-> Training / Skill / Certificate
-> Movement
-> Employee Timeline
-> Leave
-> Reports

## Instructions for Claude
- ตอบเป็นภาษาไทย
- ทำทีละ feature
- อย่าเปลี่ยน architecture เดิมโดยไม่จำเป็น
- ก่อนเพิ่ม table หรือ column ใหม่ ให้อธิบายเหตุผล
- ก่อนสร้างข้อมูลใหม่ ตรวจสอบก่อนว่าสามารถใช้ table เดิมได้หรือไม่
- Code ต้อง copy ไปใช้งานได้
- PostgreSQL เท่านั้นสำหรับ database
- ใช้ PDO Prepared Statement
- ทุก protected page ต้องตรวจ login
- POST form ต้องมี CSRF
- ทุก form ต้องมี server-side validation
- รักษา Historical Data
- ใช้ Naming Convention ตามเอกสารนี้
- ทำระบบให้ต่อยอดได้ แต่ไม่ over-engineer
