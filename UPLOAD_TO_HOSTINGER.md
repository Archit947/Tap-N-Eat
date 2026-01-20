# Upload Instructions for Hostinger

## Files to Upload (in order):

### 1. Upload database.php (updated)
**Location:** `backend/config/database.php`
**Upload to:** `/public_html/Tap-N-Eat/config/database.php`

### 2. Upload create-print-queue.php
**Location:** `backend/create-print-queue.php`
**Upload to:** `/public_html/Tap-N-Eat/create-print-queue.php`

### 3. Upload print-queue.php
**Location:** `backend/api/print-queue.php`
**Upload to:** `/public_html/Tap-N-Eat/api/print-queue.php`

### 4. Upload print-thermal.php (updated)
**Location:** `backend/api/print-thermal.php`
**Upload to:** `/public_html/Tap-N-Eat/api/print-thermal.php`

## Steps After Upload:

### Step 1: Create the Database Table
Open in browser:
```
https://qsr.catalystsolutions.eco/Tap-N-Eat/create-print-queue.php
```
You should see: ✅ print_queue table created successfully!

### Step 2: Test the API
Open in browser (should show auth error):
```
https://qsr.catalystsolutions.eco/Tap-N-Eat/api/print-queue.php
```
Expected: `{"error":"Unauthorized"}` (this is correct!)

### Step 3: Start Local Polling Service
In PowerShell on your PC:
```powershell
cd D:\projects\QSR_New\Myqsr\local-printer
$env:PRINTER_IP="192.168.0.105"
$env:API_KEY="print_secret"
$env:HOSTINGER_API="https://qsr.catalystsolutions.eco/Tap-N-Eat/api/print-queue.php"
node server-polling.cjs
```

### Step 4: Test RFID Scan
- Scan RFID card
- Check polling service terminal (should show "Found 1 print job(s)")
- Receipt should print automatically!

## Troubleshooting

If prints don't appear:
1. Check polling service terminal for errors
2. Verify printer is on and connected (IP: 192.168.0.105)
3. Check Hostinger error logs
4. Test printer: `Test-NetConnection -ComputerName 192.168.0.105 -Port 9100`

If QR code doesn't work:
- QR URL format: https://qsr.catalystsolutions.eco/Tap-N-Eat/receipt.php?id=TRANSACTION_ID
- Make sure receipt.php exists on Hostinger
