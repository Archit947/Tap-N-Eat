# QSR Admin – Operator Guide

This guide explains how an admin/operator should use the QSR Admin panel to manage employees, wallets, and transactions.

## 1) Access & Navigation
- **Open the app**: Launch the web UI served by your deployment (local or production). Log in if your deployment requires credentials.
- **Sidebar sections**:
  - Employees
  - Wallet Recharge
  - RFID Scan
  - Transaction History
  - Dashboard
  - Reports (optional/future)
- **Top header** reflects the active section name.

## 2) Employees
- **Add new employee**
  1. Go to **Employees**.
  2. Fill required fields: RFID Number, Employee ID, Employee Name, Site Name, Shift, Wallet Amount.
  3. Click **Add Employee**. Use **Cancel** to clear the form.
- **Edit employee**
  1. In **Employee List**, click **Edit** on a row.
  2. Update fields; click **Update Employee**.
- **Delete employee**
  1. In **Employee List**, click **Delete** on a row.
  2. Confirm when prompted.
- **List behavior**: Shows RFID, ID, name, site, shift, wallet balance. If empty, you will see a “No employees found” message. Wallet values are shown in ₹ with two decimals.

## 3) Wallet Recharge
- **Search individual**
  1. Go to **Wallet Recharge**.
  2. Enter RFID or Employee ID in **Find Employee** and click **Search**.
  3. If found, details and current balance appear in the card on the left.
- **Recharge individual**
  1. In **Recharge Details**, enter an amount or use quick buttons (+₹100/+₹500/+₹1000/+₹2000).
  2. (Optional) Add remarks.
  3. Click **Process Recharge**. Use **Reset** to clear.
- **Recent recharges**: Table shows recent transactions with status (Success/Failed), method, and timestamp.

## 4) RFID Scan (Meal Deduction)
- Go to **RFID Scan**.
- Current meal slot (Breakfast/Mid-Meal/Lunch/Dinner) shows rate and timing; if outside meal hours, deductions are blocked.
- To deduct a meal:
  1. Place cursor in RFID input; scan or type the RFID number.
  2. Click **Scan & Deduct**.
- On success, you’ll see meal category, amount deducted, previous/new balance, time/date. A QR code is generated for receipt viewing; printing is handled by the thermal printer service.

## 5) Transaction History
- Go to **Transaction History**.
- **Filters**: date and meal category. Click **Refresh** to re-query.
- **Export**: Click **Export PDF** to open a print-friendly view (use browser print-to-PDF).
- Table columns: Date, Time, Employee (name/ID), RFID, Meal, Amount (+/-), Previous Balance, New Balance, Site, Status.

## 6) Dashboard (Insights)
- Summaries: Total employees, total wallet balance, average per employee, today’s spend, recharge vs deduction counts.
- Meal distribution pie: shows count of meals by category.

## 7) Reports
- Placeholder menu item. Use once reporting endpoints/views are provided.

## 8) Alerts & Messages
- Success and error alerts appear at the top of the content area and auto-dismiss.
- Loading spinners display while data is fetched (employees, transactions).

## 9) Data Sources & API Notes
- The UI calls backend endpoints under `Tap-N-Eat/api/` (or the configured `VITE_API_BASE_URL`).
- Key endpoints used: `employees.php`, `wallet-recharge.php`, `rfid-scan.php`, `transactions.php`, `print-thermal.php`.

## 10) Operator Tips
- Keep the RFID input focused during busy meal periods for faster scanning.
- Verify shift and site when adding employees to avoid reporting mismatches.
- Use quick amount chips for common recharge values; prefer remarks for auditability.
- Periodically export transactions for reconciliation and backup.

## 11) Troubleshooting
- **Employee search fails**: Re-check RFID/Employee ID; ensure the employee exists in Employees.
- **Recharge rejected**: Amount must be positive; ensure backend/API is reachable.
- **RFID scan blocked**: May be outside meal hours or wallet balance insufficient; check meal slot card.
- **Printer issues**: Confirm thermal printer service is running and reachable; network mode reports success via alert.
- **Data not refreshing**: Use the section’s **Refresh** button or reload the page.

## 12) Admin Quick Start (TL;DR)
1) Add employees with correct RFID, ID, site, shift.
2) Recharge wallets from **Wallet Recharge** (search → amount → Process Recharge).
3) Deduct meals from **RFID Scan** (scan card during active meal slot).
4) Review **Transaction History**; export if needed.
5) Monitor **Dashboard** for high-level metrics.
