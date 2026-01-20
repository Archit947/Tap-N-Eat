# 🍽️ Meal Deduction System - User Guide

## Overview
The QSR system now includes automatic meal deduction based on time slots. When an employee scans their RFID card, the system automatically deducts the appropriate amount based on the current time.

---

## ⏰ Meal Time Slots & Pricing

| Meal Category | Time Slot | Amount Deducted |
|--------------|-----------|-----------------|
| 🌅 **Breakfast** | 9:00 AM - 12:00 PM | ₹20 |
| 🍛 **Lunch** | 1:00 PM - 3:00 PM | ₹50 |
| 🌙 **Dinner** | 7:00 PM - 9:00 PM | ₹50 |

**Note:** Scanning outside these time slots will be rejected with an error message.

---

## 📱 How to Use RFID Scan

### Step 1: Access RFID Scan Section
1. Open the admin dashboard at `http://localhost:5173`
2. Click on **"📱 RFID Scan"** in the sidebar menu

### Step 2: Check Current Meal Slot
- The dashboard shows the current meal slot with timing and amount
- If outside meal hours, it displays "Outside Meal Hours" with all meal timings

### Step 3: Scan Employee RFID
1. Click on the **RFID Number** input field (it auto-focuses)
2. Scan the RFID card using your card reader
3. Click **"Scan & Deduct"** button or press Enter
4. The system will:
   - Verify the employee exists
   - Check if current time is within a meal slot
   - Verify sufficient wallet balance
   - Deduct the appropriate amount
   - Log the transaction
   - Display success message with details

### Step 4: View Transaction Result
After successful scan, you'll see:
- ✅ Transaction Successful message
- Meal category (Breakfast/Lunch/Dinner)
- Amount deducted
- Previous wallet balance
- New wallet balance
- Transaction time and date

---

## 🔍 API Endpoints

### 1. Get Current Meal Info
**GET** `/api/rfid-scan.php`

**Response:**
```json
{
  "current_time": "10:30:00",
  "meal_info": {
    "category": "Breakfast",
    "amount": 20.00,
    "time_slot": "9:00 AM - 12:00 PM"
  }
}
```

### 2. Process RFID Scan
**POST** `/api/rfid-scan.php`

**Request Body:**
```json
{
  "rfid_number": "RFID001"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Transaction successful",
  "employee": {
    "name": "John Doe",
    "emp_id": "EMP001",
    "site": "Site A"
  },
  "transaction": {
    "meal_category": "Breakfast",
    "amount_deducted": 20.00,
    "previous_balance": 500.00,
    "new_balance": 480.00,
    "time": "10:30:00",
    "date": "2025-12-29",
    "time_slot": "9:00 AM - 12:00 PM"
  }
}
```

**Error Responses:**

**404 - Employee Not Found:**
```json
{
  "status": "error",
  "message": "Employee not found",
  "rfid": "RFID999"
}
```

**400 - Outside Meal Hours:**
```json
{
  "status": "error",
  "message": "Current time is not within any meal slot",
  "time_slot": "Outside meal hours",
  "current_time": "15:30:00",
  "employee": {
    "name": "John Doe",
    "wallet_balance": 500.00
  }
}
```

**400 - Insufficient Balance:**
```json
{
  "status": "error",
  "message": "Insufficient wallet balance",
  "required": 50.00,
  "available": 30.00,
  "employee": {
    "name": "John Doe",
    "emp_id": "EMP001"
  }
}
```

---

## 📊 Transaction History

### Viewing Transaction History
1. Click on **"📋 Transaction History"** in the sidebar
2. Use filters to narrow down results:
   - **Date:** Select specific date (default: today)
   - **Meal Category:** Filter by Breakfast/Lunch/Dinner or view All
3. Click **"Refresh"** to reload transactions

### Transaction Table Columns
- **Date:** Transaction date
- **Time:** Transaction time (24-hour format)
- **Employee:** Name and Employee ID
- **RFID:** RFID number used
- **Meal:** Meal category (color-coded)
  - Breakfast: Yellow background
  - Lunch: Green background
  - Dinner: Blue background
- **Amount:** Deducted amount (in red with minus sign)
- **Previous Balance:** Balance before transaction
- **New Balance:** Balance after transaction (in green, bold)
- **Site:** Employee's site name

---

## 📈 Get Transactions via API

**GET** `/api/transactions.php`

**Query Parameters:**
- `employee_id` (optional): Filter by specific employee
- `date` (optional): Filter by date (YYYY-MM-DD format)
- `meal_category` (optional): Filter by Breakfast/Lunch/Dinner
- `limit` (optional): Number of records to return (default: 100)

**Example:**
```
GET /api/transactions.php?date=2025-12-29&meal_category=Breakfast
```

**Response:**
```json
{
  "status": "success",
  "count": 25,
  "transactions": [
    {
      "id": 1,
      "employee_id": 1,
      "rfid_number": "RFID001",
      "emp_id": "EMP001",
      "emp_name": "John Doe",
      "transaction_type": "deduction",
      "meal_category": "Breakfast",
      "amount": "20.00",
      "previous_balance": "500.00",
      "new_balance": "480.00",
      "transaction_time": "10:30:00",
      "transaction_date": "2025-12-29",
      "site_name": "Site A",
      "shift": "Morning",
      "created_at": "2025-12-29 10:30:00"
    }
  ]
}
```

---

## ✅ Validation Rules

### RFID Scan Validations
1. **Employee Verification:** RFID must match an existing employee
2. **Time Verification:** Current time must be within a meal slot
3. **Balance Verification:** Employee must have sufficient wallet balance
4. **Transaction Type:** Only deduction transactions during meal times

### Transaction Logging
Every successful scan creates a transaction record with:
- Employee details (ID, RFID, Name)
- Transaction type (deduction)
- Meal category (Breakfast/Lunch/Dinner)
- Amount deducted
- Previous and new balance
- Transaction time and date
- Automatic timestamp

---

## 🔧 Database Schema

### transactions Table
```sql
CREATE TABLE transactions (
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
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
```

---

## 🧪 Testing Instructions

### Test 1: Successful Breakfast Scan
1. Set system time between 9:00 AM - 12:00 PM
2. Ensure employee has at least ₹20 in wallet
3. Scan RFID card
4. Verify ₹20 deducted and transaction logged

### Test 2: Successful Lunch Scan
1. Set system time between 1:00 PM - 3:00 PM
2. Ensure employee has at least ₹50 in wallet
3. Scan RFID card
4. Verify ₹50 deducted and transaction logged

### Test 3: Successful Dinner Scan
1. Set system time between 7:00 PM - 9:00 PM
2. Ensure employee has at least ₹50 in wallet
3. Scan RFID card
4. Verify ₹50 deducted and transaction logged

### Test 4: Outside Meal Hours
1. Set system time outside meal slots (e.g., 4:00 PM)
2. Scan RFID card
3. Verify error message displayed

### Test 5: Insufficient Balance
1. Set employee wallet balance to ₹10
2. Scan during lunch time (requires ₹50)
3. Verify insufficient balance error

### Test 6: Invalid RFID
1. Enter non-existent RFID number
2. Verify "Employee not found" error

### Test 7: Transaction History
1. Perform multiple scans throughout the day
2. Navigate to Transaction History
3. Filter by date and meal category
4. Verify all transactions displayed correctly

---

## 🚀 Production Setup

### Card Reader Integration
1. Configure RFID card reader to output to focused input field
2. Ensure card reader appends Enter key after scan
3. Test auto-submit functionality with real RFID cards
4. Position card reader near meal collection point

### Server Configuration
1. Ensure PHP server is always running: `php -S localhost:8000`
2. Configure auto-start on system boot
3. Set up proper MySQL credentials in `.env` file
4. Test database connection regularly

### Backup & Monitoring
1. Schedule regular database backups
2. Monitor transaction logs daily
3. Check wallet balances weekly
4. Generate reports for accounting

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue:** "Employee not found" error
**Solution:** Verify RFID number is registered in employee table

**Issue:** "Outside meal hours" error
**Solution:** Check system time and meal time slots

**Issue:** "Insufficient balance" error
**Solution:** Recharge employee wallet before allowing scan

**Issue:** Transaction not appearing in history
**Solution:** Check database connection and refresh transaction history page

**Issue:** Card reader not working
**Solution:** 
- Check USB connection
- Verify input field is focused
- Test card reader with text editor first

---

## 📝 Notes

- All amounts are in Indian Rupees (₹)
- Transactions are logged with millisecond precision
- Employee names and details are stored in transaction for reference
- Foreign key constraint ensures data integrity
- Automatic timestamps track when transaction was recorded
- System uses 24-hour time format internally

---

**Last Updated:** December 29, 2025
**Version:** 1.0
