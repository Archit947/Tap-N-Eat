-- =====================================================
-- QSR SYSTEM DATABASE SCHEMA
-- Database Name: qsr_system
-- Created: January 5, 2026
-- =====================================================

-- Drop existing database (optional - for fresh install)
-- DROP DATABASE IF EXISTS qsr_system;

-- Create database
CREATE DATABASE IF NOT EXISTS qsr_system;
USE qsr_system;

-- =====================================================
-- TABLE 1: employees
-- =====================================================
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_number VARCHAR(50) NOT NULL UNIQUE,
    emp_id VARCHAR(50) NOT NULL UNIQUE,
    emp_name VARCHAR(100) NOT NULL,
    site_name VARCHAR(100) NOT NULL,
    shift VARCHAR(50) NOT NULL,
    wallet_amount DECIMAL(10, 2) DEFAULT 0.00,
    razorpay_wallet_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rfid (rfid_number),
    INDEX idx_emp_id (emp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE 2: admin_users
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE 3: transactions
-- =====================================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    rfid_number VARCHAR(50) NOT NULL,
    emp_id VARCHAR(50) NOT NULL,
    emp_name VARCHAR(100) NOT NULL,
    transaction_type ENUM('deduction', 'recharge') NOT NULL,
    meal_category VARCHAR(50) NULL,
    amount DECIMAL(10, 2) NOT NULL,
    previous_balance DECIMAL(10, 2) NOT NULL,
    new_balance DECIMAL(10, 2) NOT NULL,
    transaction_time TIME NOT NULL,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_transaction_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default admin user
-- Username: admin
-- Password: admin123 (bcrypt hashed)
INSERT INTO admin_users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@qsr.com')
ON DUPLICATE KEY UPDATE username=VALUES(username);

-- Insert sample employees
INSERT INTO employees (rfid_number, emp_id, emp_name, site_name, shift, wallet_amount) VALUES
('RFID001', 'EMP001', 'John Doe', 'Site A', 'Morning', 1000.00),
('RFID002', 'EMP002', 'Jane Smith', 'Site B', 'Evening', 500.00),
('RFID003', 'EMP003', 'Mike Johnson', 'Site A', 'Night', 750.00)
ON DUPLICATE KEY UPDATE emp_name=VALUES(emp_name);

-- =====================================================
-- TABLE DESCRIPTIONS
-- =====================================================

-- TABLE: employees
-- Purpose: Store employee information and wallet balances
-- Columns:
--   id: Unique identifier (auto-increment)
--   rfid_number: RFID card number (unique)
--   emp_id: Employee ID (unique)
--   emp_name: Employee full name
--   site_name: Work site/location
--   shift: Work shift (Morning/Evening/Night)
--   wallet_amount: Current wallet balance in rupees
--   razorpay_wallet_id: Razorpay integration ID (optional)
--   created_at: Record creation timestamp
--   updated_at: Last modification timestamp

-- TABLE: admin_users
-- Purpose: Store admin login credentials
-- Columns:
--   id: Unique identifier (auto-increment)
--   username: Login username (unique)
--   password: Bcrypt hashed password
--   email: Admin email address
--   created_at: Account creation timestamp

-- TABLE: transactions
-- Purpose: Track all meal deductions and wallet recharges
-- Columns:
--   id: Transaction identifier (auto-increment)
--   employee_id: Foreign key to employees table
--   rfid_number: RFID card number (for reference)
--   emp_id: Employee ID (for reference)
--   emp_name: Employee name (for reference)
--   transaction_type: 'deduction' for meals, 'recharge' for wallet topup
--   meal_category: Type of meal (Breakfast/Lunch/Dinner/etc)
--   amount: Transaction amount in rupees
--   previous_balance: Wallet balance before transaction
--   new_balance: Wallet balance after transaction
--   transaction_time: Time of transaction (HH:MM:SS)
--   transaction_date: Date of transaction (YYYY-MM-DD)
--   created_at: Record creation timestamp


-- =====================================================
-- CONSTRAINTS & RELATIONSHIPS
-- =====================================================

-- Foreign Key Relationship:
-- transactions.employee_id → employees.id (CASCADE DELETE)
-- If an employee is deleted, all their transactions are also deleted

-- Unique Constraints:
-- employees.rfid_number (unique)
-- employees.emp_id (unique)
-- admin_users.username (unique)

-- =====================================================
-- MEAL PRICING (Reference)
-- =====================================================

-- Breakfast:    6:00 AM - 11:59 AM  = ₹20
-- Mid-Meal:    12:00 PM - 12:59 PM  = ₹30
-- Lunch:        1:00 PM -  2:59 PM  = ₹50
-- Snacks:       3:00 PM -  4:59 PM  = ₹15
-- Dinner:       5:00 PM onwards     = ₹40

-- =====================================================
-- COMMON QUERIES
-- =====================================================

-- Get all employees with their wallet balance:
-- SELECT * FROM employees ORDER BY emp_name;

-- Get employee transactions by date:
-- SELECT * FROM transactions 
-- WHERE employee_id = 1 AND transaction_date = '2026-01-05'
-- ORDER BY transaction_time DESC;

-- Get total meal deductions for a date:
-- SELECT emp_name, COUNT(*) as meals, SUM(amount) as total
-- FROM transactions 
-- WHERE transaction_type = 'deduction' AND transaction_date = '2026-01-05'
-- GROUP BY emp_id ORDER BY total DESC;

-- Get wallet recharge history:
-- SELECT * FROM transactions 
-- WHERE transaction_type = 'recharge'
-- ORDER BY created_at DESC;

-- Get current wallet balance:
-- SELECT emp_name, wallet_amount FROM employees WHERE emp_id = 'EMP001';

-- =====================================================
-- END OF SCHEMA
-- =====================================================
