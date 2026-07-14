# Campus Complaint Management System

A robust, highly-normalized relational database backend designed to streamline, track, and resolve maintenance and facility complaints within a university campus. This system seamlessly bridges the gap between students reporting infrastructure issues, administrators dispatching tasks, and maintenance staff executing repairs.

---

## 📌 System Architecture & Schema Design

The database is built on **MySQL** and features a highly normalized relational schema designed for strict data integrity, fast index-based searching, and comprehensive historical auditing.

```
     ┌────────────────┐
                             │     users      │
                             └───────┬────────┘
         ┌───────────────────────────┼───────────────────────────┐
         ▼                           ▼                           ▼
   ┌───────────┐               ┌───────────┐               ┌───────────┐
   │ students  │               │  admins   │               │maint_staff│
   └─────┬─────┘               └─────┬─────┘               └─────┬─────┘
         │                           │                           │
         │                           │   ┌───────────────────────┤
         ▼                           ▼   ▼                       ▼
   ┌───────────┐               ┌───────────┐               ┌───────────┐
   │complaints ◄───────────────┤assignments│               │notif_queue│
   └─────┬─────┘               └─────┬─────┘               └───────────┘
         │                           │
         ├───────────────────────────┤
         ▼                           ▼
   ┌───────────┐               ┌───────────┐
   │ feedback  │               │  updates  │
   └───────────┘               └─────┬─────┘
                                     │
   ══════════════════════════════════╪══════════════════════════════════
     LOOKUP TABLES (Master Data)     │
   ══════════════════════════════════╪══════════════════════════════════
         │                           │
         ├──────────────┐            │
         ▼              ▼            ▼
   ┌───────────┐  ┌───────────┐┌───────────┐
   │ buildings │  │categories ││  status   │    
   └───────────┘  └───────────┘└───────────┘              
```

### Key Modules
*   **Identity & Access Management:** Centralized `users` table subclassed into specialized domain tables (`students`, `maintenance_staff`, `admins`) utilizing strong 1:1 relationships.
*   **Infrastructure Cataloging:** Core structural lookup data decoupled into managed `buildings` and `complaint_categories`.
*   **Workflow & Dispatch Engine:** Comprehensive ticketing tracking via `complaints`, granular work-order dispatches in `assignments`, and incremental field updates in `complaint_updates`.
*   **Quality Assurance & Feedback:** An integrated loop via the `feedback` table allowing students to score closed tickets.
*   **Asynchronous Communications:** A lightweight `notifications` queue system for decoupled user-alert signaling.

---

## 🗄️ Database Entity Profiles

### 1. User & Role Entities
*   **`users`**: The core authentication and identity directory. Stores names, verified unique email addresses, phone contacts, secure password hashes, role classifications (`admin`, `student`, `staff`), and life-cycle flags (`active`, `inactive`).
*   **`students`**: Subclass of `users`. Maps academic context, including department tracking, academic semester, and physical on-campus lodging details (`building_id`, `room_no`).
*   **`maintenance_staff`**: Subclass of `users`. Contains professional meta-data (employee identifiers, specialized skill domains, and live task availability statuses like `available`, `busy`, or `offline`).
*   **`admins`**: Subclass of `users`. Explicitly maps privileged dispatch users and system administrators.

### 2. Operations & Lifecycle Tracking
*   **`complaints`**: The central ticket repository. Stores contextual issue data (title, text description, structural priority flags: `Low`, `Medium`, `High`, and file paths for uploaded asset images). Implements performance optimization indexes on performance-critical foreign keys (`student_id`, `category_id`, `status_id`).
*   **`assignments`**: The admin-to-staff work order bridge. Logs precise workflow state timestamps (`assigned_date`, `accepted_date`, `completed_date`) along with resolution notes and final repair photos.
*   **`complaint_updates`**: Mid-lifecycle auditing mechanism. Enables field technicians to document progress, supply text notes, and attach incremental verification photos during a fix.
*   **`feedback`**: Post-resolution evaluation mechanism collecting `rating` metrics (constrained from **1** to **5** stars via a database `CHECK` constraint) and explicit textual commentary from students.

---

## 🚦 Ticket Lifecycle States

Complaints migrate through deterministic state-machine phases defined within the `complaint_status` seed records:

```
[Pending] ──► [Assigned] ──► [Accepted] ──► [In Progress] ──► [Resolved] ──► [Closed]
     │                                                            ▲
     └──────────────────────────► [Rejected] ─────────────────────┘
```

1.  **Pending:** Freshly logged issues waiting for administration evaluation.
2.  **Assigned:** Dispatched by an admin to a selected maintenance specialist.
3.  **Accepted:** Acknowledged and claimed by the assigned technician.
4.  **In Progress:** Active work occurring on-site (incremental notes logged in `complaint_updates`).
5.  **Resolved:** Marked fixed by the technician; pending user or admin verification.
6.  **Closed:** Officially finalized. The lifecycle loop completes (Feedback submission window opens).
7.  **Rejected:** Flags invalid, out-of-scope, or duplicate submissions.

---

## ⚡ Setup & Initialization

### Prerequisites
*   MySQL Server (v5.7 or higher / v8.0+ recommended)

### Deployment Steps

1. Clone this repository or copy the SQL schema script.
2. Import the schema script into your target MySQL instance using the CLI:
   ```bash
   mysql -u your_username -p < schema.sql
   ```
   *(Alternatively, copy and run the raw SQL inside your preferred database IDE such as DBeaver, phpMyAdmin, DataGrip, or MySQL Workbench).*

### Seed Data Included
*   **Buildings:** Default entries for `Academic Building`, `Library`, `Hostel`, `Cafeteria`, `Laboratory`, `Auditorium`, and `Playground`.
*   **Categories:** Pre-populated common issue sectors like `Electrical`, `Plumbing`, `Internet/Wi-Fi`, `AC`, `Water Supply`, and `Cleanliness`.
*   **Workflow States:** Populated engine statuses from `Pending` through `Closed`.
*   **Root Administrator:**
    *   **Email / Login:** `admin@campus.edu`
    *   **Initial Password Hash:** `$2y$10$replace_with_hash` **

---

## 🛡️ Referential Integrity & Security Practices

*   **Cascading Rules (`ON DELETE CASCADE`):** 
    *   Purging a base identity inside `users` immediately and safely drops records across its linked context tables (`students`, `maintenance_staff`, `admins`) along with its target `notifications`.
    *   Removing an active ticket within `complaints` completely flushes its cascading logs across dependencies (`assignments`, `feedback`, `complaint_updates`) preventing database bloat and orphan references.
*   **Nullification Handling (`ON DELETE SET NULL`):** Modifying or tearing down structural master-data like physical `buildings` doesn't drop dependent user data; it cleanly resets student housing locations back to `NULL`.
*   **Password Requirements:** Passwords in the `users` table are configured to accommodate high-entropy cryptographic strings. **Always secure credentials with modern hashing standards (e.g., bcrypt / Argon2) prior to committing data.**
