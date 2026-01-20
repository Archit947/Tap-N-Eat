-- Create database
CREATE DATABASE IF NOT EXISTS qsr_system;
USE qsr_system;

-- Create employees table
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@qsr.com');

-- Sample employee data
INSERT INTO employees (rfid_number, emp_id, emp_name, site_name, shift, wallet_amount) VALUES
('RFID001', 'EMP001', 'John Doe', 'Site A', 'Morning', 1000.00),
('RFID002', 'EMP002', 'Jane Smith', 'Site B', 'Evening', 500.00),
('RFID003', 'EMP003', 'Mike Johnson', 'Site A', 'Night', 750.00);
