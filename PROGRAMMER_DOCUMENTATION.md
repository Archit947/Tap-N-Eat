# QSR Admin Platform – Programmer Documentation
Version: v1.0.0
Environment: Production / Staging / Development
Prepared By: Engineering
Date: 09 Jan 2026
Organization: (update if applicable)

## Table of Contents
- [1. Project Overview](#1-project-overview)
  - [1.1 Purpose](#11-purpose)
  - [1.2 Scope](#12-scope)
  - [1.3 Target Users](#13-target-users)
- [2. Technology Stack](#2-technology-stack)
  - [2.1 Frontend](#21-frontend)
  - [2.2 Backend](#22-backend)
  - [2.3 Database](#23-database)
  - [2.4 Hosting and Deployment](#24-hosting-and-deployment)
- [3. System Architecture](#3-system-architecture)
  - [3.1 High-Level Architecture](#31-high-level-architecture)
  - [3.2 Folder Structure](#32-folder-structure)
- [4. Installation and Setup](#4-installation-and-setup)
  - [4.1 System Requirements](#41-system-requirements)
  - [4.2 Local Setup](#42-local-setup)
  - [4.3 Environment Variables](#43-environment-variables)
- [5. Database Design](#5-database-design)
  - [5.1 ER Diagram (textual)](#51-er-diagram-textual)
  - [5.2 Tables](#52-tables)
- [6. API Documentation](#6-api-documentation)
  - [6.1 Authentication](#61-authentication)
  - [6.2 Employees](#62-employees)
  - [6.3 Wallet Recharge](#63-wallet-recharge)
  - [6.4 RFID Scan](#64-rfid-scan)
  - [6.5 Transactions](#65-transactions)
  - [6.6 API Conventions](#66-api-conventions)
- [7. Roles and Permissions](#7-roles-and-permissions)
- [8. Security Implementation](#8-security-implementation)
- [9. Error Handling and Logs](#9-error-handling-and-logs)
- [10. Deployment Guide](#10-deployment-guide)
- [11. Maintenance and Future Enhancements](#11-maintenance-and-future-enhancements)
- [12. Appendix](#12-appendix)

## 1. Project Overview
QSR Admin is a web-based admin console for managing employees, RFID-based meal deductions, wallet recharges, and transaction visibility for a quick-service restaurant environment.

### 1.1 Purpose
- Provide admins a unified interface to manage employee records and wallet balances.
- Track meal deductions via RFID scans and wallet recharges with audit history.
- Offer basic analytics (counts, balances, distribution) for operations.

### 1.2 Scope
Included:
- Employee CRUD with wallet balance management.
- Individual and bulk wallet recharge.
- RFID scan for meal deduction with thermal print trigger.
- Transaction history with filters and export-to-print.
- Basic dashboard insights.
Excluded (current release):
- Granular RBAC beyond admin user.
- Full payment gateway integration (Razorpay placeholders only).
- Advanced reporting or analytics warehouse.
- Mobile apps; this is web-only.

### 1.3 Target Users
- Admin: Full access to all features.
- Staff: Operational use (scan, recharge) if granted UI access.
- Super Admin: Not distinct yet; align with Admin.
- Customers: Not exposed to this UI.

## 2. Technology Stack

### 2.1 Frontend
Technology | Purpose
--- | ---
React (Vite) | SPA shell and UI rendering
React Router | Client routing (planned/partial)
Fetch API | HTTP calls to backend
CSS (custom) | Styling (no utility framework configured)

### 2.2 Backend
Technology | Purpose
--- | ---
PHP (with PDO) | API endpoints and DB access
Native PHP routing | Simple endpoint per file (no framework)
Express (node) | Present but unused in production; rely on PHP APIs

### 2.3 Database
DB | Usage
--- | ---
MySQL | Primary data store for employees, admin users, transactions

### 2.4 Hosting and Deployment
- Frontend: Static hosting (Hostinger or any static-capable host)
- Backend: PHP hosting (VPS/shared) serving backend/api endpoints
- Database: MySQL (local or managed)

## 3. System Architecture

### 3.1 High-Level Architecture
User (browser) → Frontend (React) → Backend PHP API → MySQL.
- Frontend calls the PHP API under `/Tap-N-Eat/api` or `VITE_API_BASE_URL`.
- Backend uses PDO to query MySQL and returns JSON.
- RFID scan triggers deduction logic and may call thermal print endpoint.

### 3.2 Folder Structure
Key paths (workspace-relative):
- Frontend app: [src](src), entry [src/main.jsx](src/main.jsx), root component [src/App.jsx](src/App.jsx), dashboard UI [src/components/AdminDashboard.jsx](src/components/AdminDashboard.jsx), styles [src/App.css](src/App.css), [src/index.css](src/index.css).
- Backend PHP APIs: [backend/api](backend/api) (employees.php, wallet-recharge.php, rfid-scan.php, transactions.php, print-thermal*.php).
- Backend config: [backend/config](backend/config) (database.php, env_loader.php, .env template).
- SQL schema: [backend/qsr_database_schema.sql](backend/qsr_database_schema.sql) and seed [backend/database.sql](backend/database.sql).
- Local printer service: [local-printer](local-printer) (node scripts to poll and print).

## 4. Installation and Setup

### 4.1 System Requirements
- Node.js 18+ (for frontend build/dev)
- npm 9+
- PHP 8.1+ with PDO MySQL
- MySQL 8.0+ (5.7+ likely works)
- OS: Windows, macOS, or Linux

### 4.2 Local Setup
Frontend:
1) Clone repo and open `Myqsr` root.
2) `npm install`
3) Set `VITE_API_BASE_URL` in a `.env` file (see 4.3).
4) Run dev server: `npm run dev`
5) Build for production: `npm run build`

Backend (PHP):
1) Ensure PHP and MySQL are running.
2) Copy backend to your PHP server root or configure virtual host.
3) Create DB and import schema: `mysql -u <user> -p < database.sql` or use [backend/qsr_database_schema.sql](backend/qsr_database_schema.sql).
4) Create `backend/.env` with DB credentials (see 4.3).
5) Access APIs under `backend/api/` (e.g., `http://localhost/.../backend/api/employees.php`).

Local printer service (optional):
1) Install Node 18+.
2) Inside [local-printer](local-printer), run `npm install` (if package.json exists) or `npm run start` per your setup; scripts include `server.cjs` and `server-polling.cjs`.

### 4.3 Environment Variables
Frontend (.env):
- `VITE_API_BASE_URL` – Base URL for PHP APIs (e.g., `http://localhost/Myqsr/backend/api`).

Backend (backend/.env):
- `DB_HOST` – MySQL host
- `DB_NAME` – Database name (default `qsr_system`)
- `DB_USER` – MySQL user
- `DB_PASSWORD` – MySQL password

## 5. Database Design

### 5.1 ER Diagram (textual)
- `employees` 1–N `transactions` (transactions.employee_id → employees.id, cascade delete)
- `admin_users` is standalone for admin auth.

### 5.2 Tables
- employees: id, rfid_number (uniq), emp_id (uniq), emp_name, site_name, shift, wallet_amount, razorpay_wallet_id, timestamps.
- admin_users: id, username (uniq), password (bcrypt), email, created_at.
- transactions: id, employee_id (FK), rfid_number, emp_id, emp_name, transaction_type (deduction/recharge), meal_category, amount, previous_balance, new_balance, transaction_time, transaction_date, created_at.
See full schema in [backend/qsr_database_schema.sql](backend/qsr_database_schema.sql).

## 6. API Documentation
Base URL: `${VITE_API_BASE_URL}` (frontend) or `/backend/api` (server-relative).

### 6.1 Authentication
- POST `/login.php` (if present) – authenticate admin (check backend implementation; default admin user seeded in SQL).

### 6.2 Employees
- GET `/employees.php` – list employees.
- GET `/employees.php?id={id}` – get one.
- POST `/employees.php` – create employee.
- PUT `/employees.php` – update employee.
- DELETE `/employees.php` – delete employee.
Request/response: JSON; see backend files for exact fields.

### 6.3 Wallet Recharge
- POST `/wallet-recharge.php` – recharge wallet (fields: employee_id, amount). Supports bulk via `bulk_recharge: true` and `amount`.
- GET `/wallet-recharge.php?search={rfid|emp_id}` – lookup employee and wallet balance.

### 6.4 RFID Scan
- POST `/rfid-scan.php` – deduct meal by RFID; expects `rfid_number` and uses server meal-slot logic.

### 6.5 Transactions
- GET `/transactions.php?limit=100&date=YYYY-MM-DD&meal_category=...` – list transactions with filters.

### 6.6 API Conventions
- Content-Type: `application/json` for POST/PUT/DELETE bodies.
- Responses: JSON with status/message; amounts in decimal strings.
- Errors: Use HTTP 4xx/5xx with JSON body when possible.

Sample request (create employee):
```
POST /employees.php
{
  "rfid_number": "RFID123",
  "emp_id": "EMP123",
  "emp_name": "Jane Doe",
  "site_name": "Site A",
  "shift": "Morning"
}
```
Sample response:
```
{
  "status": true,
  "message": "Employee created",
  "id": 42
}
```

## 7. Roles and Permissions
Role | Permissions
--- | ---
Admin | Full CRUD on employees, recharge, scan, transactions, dashboard.
Staff (future) | Limited to scan/recharge if UI exposes.
User | Not applicable in current build.

## 8. Security Implementation
- Passwords stored bcrypt-hashed in `admin_users` seed; change defaults in production.
- CORS headers enabled in backend for broad access; tighten per environment.
- Database access via PDO with prepared statements.
- Suggested: add JWT-based auth and role checks for APIs (not yet implemented).
- Suggested: configure HTTPS and restrict API origin in production.

## 9. Error Handling and Logs
- Backend returns JSON error with HTTP 500 on DB failures; see [backend/config/database.php](backend/config/database.php).
- PHP error logs: check server logs or configured `error_log` for details.
- Frontend shows toast/alert banners for API errors.
- Common HTTP codes: 200 success; 400 validation; 401/403 auth (add when implemented); 500 server/DB.

## 10. Deployment Guide
Frontend:
1) `npm install && npm run build`
2) Deploy `dist/` to static hosting (Hostinger or similar).
3) Set correct `VITE_API_BASE_URL` for the deployed backend.

Backend:
1) Deploy `backend/` to PHP host.
2) Configure virtual host or path so `/backend/api` is reachable.
3) Set `backend/.env` with DB creds; ensure PDO MySQL enabled.
4) Import schema [backend/qsr_database_schema.sql](backend/qsr_database_schema.sql) into MySQL.
5) Verify endpoints with a curl or Postman smoke test.

Database:
- Ensure MySQL reachable from backend host.
- Run schema migrations manually via the provided SQL files.

Local printer service (optional):
- Deploy node service where the printer is connected; configure it to poll the backend if required by your flow.

## 11. Maintenance and Future Enhancements
- Backups: nightly MySQL dump; store securely.
- Monitoring: add health checks for API and DB connectivity.
- Scaling: move backend to VPS with PHP-FPM, front to CDN; consider API rate limits.
- Roadmap: proper auth (JWT), role-based UI, payment gateway integration, richer reporting, and automated tests.

## 12. Appendix
- Schema: [backend/qsr_database_schema.sql](backend/qsr_database_schema.sql)
- Seed data: [backend/database.sql](backend/database.sql)
- Backend config: [backend/config/database.php](backend/config/database.php)
- Frontend entry: [src/main.jsx](src/main.jsx), dashboard UI [src/components/AdminDashboard.jsx](src/components/AdminDashboard.jsx)
- Printer service: [local-printer](local-printer)
