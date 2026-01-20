# Wallet Recharge Feature - Complete Guide

## ✅ Features Implemented

### 1. **Bulk Recharge** 💰
- Add wallet amount to ALL employees at once
- Simple interface with amount input
- Confirmation before processing
- Shows number of employees recharged

### 2. **Individual Employee Recharge** 🔍
- Search employee by RFID Number OR Employee ID
- Displays complete employee details:
  - RFID Number
  - Employee ID
  - Name
  - Site
  - Shift
  - Current Wallet Balance (highlighted in green)
- Add specific amount to individual employee wallet
- Real-time wallet balance update

## 🎯 How to Use

### Access Wallet Recharge:
1. Open your React app: http://localhost:5173
2. Click on **💰 Wallet Recharge** in the sidebar

### Bulk Recharge All Employees:
1. Enter amount in "Amount to Add (₹)" field
2. Click "Recharge All Employees"
3. Confirm the action
4. All employees' wallets will be credited instantly

### Individual Employee Recharge:
1. Enter RFID Number (e.g., RFID001) OR Employee ID (e.g., EMP001)
2. Click "Search Employee" or press Enter
3. Employee details will appear with current wallet balance
4. Enter amount to add
5. Click "Add to Wallet"
6. Wallet will be credited instantly

## 📡 API Endpoints

### Search Employee
```
GET /api/wallet-recharge.php?search=RFID001
GET /api/wallet-recharge.php?search=EMP001
```

### Individual Recharge
```
POST /api/wallet-recharge.php
{
  "employee_id": 1,
  "amount": 500.00
}
```

### Bulk Recharge
```
POST /api/wallet-recharge.php
{
  "bulk_recharge": true,
  "amount": 1000.00
}
```

## 🎨 UI Features

### Wallet Recharge Page:
- ✅ Clean, intuitive interface
- ✅ Separate sections for bulk and individual recharge
- ✅ Real-time search results
- ✅ Employee details card with current balance
- ✅ Success/error notifications
- ✅ Amount validation
- ✅ Confirmation dialogs for bulk operations

### Visual Highlights:
- Current wallet balance shown in **green** and **bold**
- Employee details in a highlighted card
- Clear labels and descriptions
- Responsive design

## 🔒 Validations

1. **Bulk Recharge:**
   - Amount must be greater than 0
   - Confirmation required before processing
   - Shows number of employees affected

2. **Individual Recharge:**
   - Search query cannot be empty
   - Employee must be found before recharging
   - Amount must be greater than 0
   - Real-time balance update after recharge

## 💡 Usage Examples

### Example 1: Bulk Recharge
```
Scenario: Monthly stipend of ₹1000 for all employees
1. Click "Wallet Recharge" in sidebar
2. Enter "1000" in bulk recharge amount
3. Click "Recharge All Employees"
4. Confirm
5. Done! All employees receive ₹1000
```

### Example 2: Individual Recharge
```
Scenario: Special bonus for employee EMP001
1. Click "Wallet Recharge" in sidebar
2. Enter "EMP001" in search box
3. Click "Search Employee"
4. Review employee details
5. Enter "500" in amount field
6. Click "Add to Wallet"
7. Done! EMP001 receives ₹500
```

## 📊 Benefits

1. **Fast Bulk Operations:** Recharge hundreds of employees in seconds
2. **Flexible Individual Recharge:** Quick search by RFID or Employee ID
3. **Real-time Updates:** Instant wallet balance updates
4. **Audit Trail:** All transactions can be logged
5. **User-Friendly:** Simple interface for admin users
6. **Error Handling:** Clear error messages for better user experience

## 🚀 Testing

1. **Test Bulk Recharge:**
   - Add ₹100 to all employees
   - Check employee table - all wallets should increase by ₹100

2. **Test Individual Recharge:**
   - Search for RFID001
   - Add ₹50 to wallet
   - Check that only that employee's wallet increased

3. **Test Search:**
   - Try searching with RFID number
   - Try searching with Employee ID
   - Try invalid search - should show error

## 🔧 Backend Files

- `backend/api/wallet-recharge.php` - Wallet recharge API
- `backend/api/employees.php` - Employee management API
- `backend/config/database.php` - Database connection
- `backend/.env` - Database credentials

## 🎨 Frontend Files

- `src/components/AdminDashboard.jsx` - Main dashboard component
- `src/components/AdminDashboard.css` - Dashboard styles

## 📝 Notes

- All amounts are in Indian Rupees (₹)
- Amounts are stored with 2 decimal places
- Search is case-sensitive
- Wallet balance cannot be negative
- Bulk recharge affects all employees in database
- Individual recharge only affects searched employee
