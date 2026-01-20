# QSR Employee Management System - Backend

## Setup Instructions

### 1. Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server (XAMPP, WAMP, or LAMP recommended)

### 2. Database Setup
1. Start your MySQL server
2. Open phpMyAdmin or MySQL command line
3. Import the database:
   ```sql
   source backend/database.sql
   ```
   Or manually execute the SQL file in phpMyAdmin

4. Update database credentials in `backend/config/database.php` if needed:
   ```php
   private $host = "localhost";
   private $db_name = "qsr_system";
   private $username = "root";
   private $password = "";
   ```

### 3. Server Setup

#### For XAMPP/WAMP:
1. Copy the entire project to `htdocs` folder
2. Make sure Apache and MySQL are running
3. Access admin dashboard at: `http://localhost/QSR_New/Myqsr/backend/admin/admin.html`

#### For other servers:
1. Configure your web server to point to the project directory
2. Ensure PHP has permission to access the files
3. Enable mod_rewrite if using Apache

### 4. API Endpoints

Base URL: `http://localhost/QSR_New/Myqsr/backend/api`

#### Employees API (`/employees.php`)

**Get all employees:**
```
GET /employees.php
```

**Get single employee:**
```
GET /employees.php?id=1
```

**Create employee:**
```
POST /employees.php
Content-Type: application/json

{
    "rfid_number": "RFID001",
    "emp_id": "EMP001",
    "emp_name": "John Doe",
    "site_name": "Site A",
    "shift": "Morning"
}
```

**Update employee:**
```
PUT /employees.php
Content-Type: application/json

{
    "id": 1,
    "rfid_number": "RFID001",
    "emp_id": "EMP001",
    "emp_name": "John Doe",
    "site_name": "Site A",
    "shift": "Evening"
}
```

**Delete employee:**
```
DELETE /employees.php
Content-Type: application/json

{
    "id": 1
}
```

### 5. Admin Dashboard

**Access:** `http://localhost/QSR_New/Myqsr/backend/admin/admin.html`

**Default Admin Credentials:**
- Username: admin
- Password: admin123

**Features:**
- Add new employees
- View all employees in a table
- Edit existing employee details
- Delete employees
- Search and filter functionality

### 6. Configuration

Update the API base URL in `backend/admin/admin.js`:
```javascript
const API_BASE_URL = 'http://localhost/QSR_New/Myqsr/backend/api';
```

### 7. Troubleshooting

**CORS Issues:**
- The API already includes CORS headers
- If you still face issues, check your browser console

**Database Connection Failed:**
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure database `qsr_system` exists

**API not responding:**
- Check if Apache/PHP is running
- Verify the file paths in URLs
- Check PHP error logs

### 8. Security Notes

- Change default admin password in production
- Use environment variables for database credentials
- Implement proper authentication and authorization
- Add input validation and sanitization
- Use prepared statements (already implemented)

### 9. File Structure

```
backend/
├── config/
│   └── database.php          # Database connection
├── api/
│   └── employees.php         # Employee CRUD API
├── admin/
│   ├── admin.html           # Admin dashboard UI
│   └── admin.js             # Dashboard JavaScript
├── database.sql              # Database schema and sample data
└── README.md                # This file
```

### 10. Future Enhancements

- User authentication system
- Employee attendance tracking
- Report generation
- Export to Excel/PDF
- Real-time RFID scanning integration
- Role-based access control
