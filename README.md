# Campus Complaint Management System

A MySQL-based database system for managing maintenance complaints within a university campus. The system allows students to report campus issues, administrators to assign maintenance staff, and staff members to update repair progress until the complaint is resolved.

---

## Project Overview

This project is designed to simplify the maintenance complaint process on a university campus by keeping all complaint records in a centralized database.

### User Roles

- **Student**
  - Submit complaints
  - Track complaint status
  - Provide feedback after resolution

- **Administrator**
  - Manage complaints
  - Assign maintenance staff
  - Monitor complaint progress

- **Maintenance Staff**
  - View assigned complaints
  - Update repair progress
  - Complete assigned work

---

## Database Structure

```
Users
│
├── Students
├── Admins
└── Maintenance Staff
        │
        ▼
    Complaints
        │
 ┌──────┼──────────┐
 ▼      ▼          ▼
Assignments  Complaint Updates  Feedback

Lookup Tables
--------------
• Buildings
• Complaint Categories
• Complaint Status

Notifications
```

---

## Database Tables

### users

Stores account information for every user.

| Field |
|------|
| user_id |
| name |
| email |
| phone |
| password |
| role |
| status |
| created_at |
| updated_at |

---

### students

Stores student-specific information.

| Field |
|------|
| student_id |
| user_id |
| student_number |
| department |
| semester |
| building_id |
| room_no |

---

### maintenance_staff

Stores maintenance employee information.

| Field |
|------|
| staff_id |
| user_id |
| employee_id |
| designation |
| specialization |
| phone |
| availability |

Availability values:

- Available
- Busy
- Offline

---

### admins

Stores administrator records linked with users.

---

### buildings

Stores the list of campus buildings.

Sample records include:

- Academic Building
- Library
- Hostel
- Cafeteria
- Laboratory
- Auditorium
- Playground

---

### complaint_categories

Stores complaint categories.

Examples:

- Electrical
- Plumbing
- Internet/Wi-Fi
- Projector
- Fan
- AC
- Furniture
- Hostel Issues
- Cleanliness
- Others

---

### complaint_status

Stores complaint status values.

- Pending
- Assigned
- Accepted
- In Progress
- Resolved
- Rejected
- Closed

---

### complaints

The main table of the system.

Stores:

- Student
- Category
- Building
- Status
- Title
- Description
- Image
- Priority
- Created Time
- Updated Time
- Resolved Time

Priority values:

- Low
- Medium
- High

Indexes are created on:

- student_id
- category_id
- status_id

---

### assignments

Stores maintenance assignments.

Includes:

- Assigned Staff
- Assigned By
- Assigned Date
- Accepted Date
- Completed Date
- Repair Notes
- Repair Image
- Assignment Status

Assignment status values:

- Assigned
- Accepted
- Rejected
- Completed

---

### complaint_updates

Stores progress updates made by maintenance staff.

Includes:

- Complaint
- Staff
- Status
- Progress Note
- Progress Image
- Created Time

---

### feedback

Allows students to rate completed complaints.

Stores:

- Rating (1–5)
- Comments
- Submission Date

---

### notifications

Stores notifications sent to users.

Includes:

- Title
- Message
- Read Status
- Created Time

---

## Complaint Workflow

```
Pending
   │
   ▼
Assigned
   │
   ▼
Accepted
   │
   ▼
In Progress
   │
   ▼
Resolved
   │
   ▼
Closed
```

If a complaint cannot be processed, it may be marked as:

```
Rejected
```

---

## Database Features

- Relational database using MySQL
- Primary and Foreign Key relationships
- Lookup tables
- ENUM data types
- CHECK constraint for feedback ratings
- Timestamp tracking
- Indexed complaint records
- Notification system
- Student feedback module
- Cascading delete support

---

## Referential Integrity

### ON DELETE CASCADE

Deleting a user automatically removes related records from:

- Students
- Maintenance Staff
- Admins
- Notifications

Deleting a complaint automatically removes:

- Assignments
- Complaint Updates
- Feedback

### ON DELETE SET NULL

If a building is deleted, the building reference in the **Students** table is automatically set to **NULL**.

---

## Sample Data

The SQL script includes sample data for:

- 7 Buildings
- 12 Complaint Categories
- 7 Complaint Statuses
- 1 Administrator
- 5 Students
- 2 Maintenance Staff
- Sample Complaints
- Assignments
- Complaint Updates
- Feedback
- Notifications

---

## Default Administrator

| Email | Password |
|--------|----------|
| admin@campus.edu | admin123 |


---

## Technologies Used

- MySQL
- SQL
- Relational Database Design
- Foreign Keys
- Constraints
- Indexing

---

## Team Members

- **Hafsa Alam Chowdhury**
- **Khadija Haque Zara**

---

## Project Objective

The objective of this project is to develop a database system that helps universities manage maintenance complaints efficiently. Students can report campus issues, administrators can assign maintenance staff, and maintenance staff can update repair progress until the complaint is resolved. The system keeps complaint information organized and maintains a complete history of every complaint.
