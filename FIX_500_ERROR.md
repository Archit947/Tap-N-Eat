# FIX 500 ERROR - STEP BY STEP

## ⚠️ Current Issue
- 500 error from print-thermal.php
- JSON parse error in frontend
- Print not working

## 🔧 SOLUTION

### STEP 1: Upload ALL Files to Hostinger

**Upload these files via FileZilla or cPanel File Manager:**

| File on Your PC | Upload to Hostinger |
|-----------------|---------------------|
| `backend/config/database.php` | `/public_html/Tap-N-Eat/config/database.php` |
| `backend/create-print-queue.php` | `/public_html/Tap-N-Eat/create-print-queue.php` |
| `backend/api/print-queue.php` | `/public_html/Tap-N-Eat/api/print-queue.php` |
| `backend/api/print-thermal.php` | `/public_html/Tap-N-Eat/api/print-thermal.php` |
| `backend/api/print-thermal-debug.php` | `/public_html/Tap-N-Eat/api/print-thermal-debug.php` (NEW) |
| `backend/api/test-setup.php` | `/public_html/Tap-N-Eat/api/test-setup.php` |

**Important:** Make sure you OVERWRITE existing files!

---

### STEP 2: Run Diagnostic Test

Open this URL in browser:
```
https://qsr.catalystsolutions.eco/Tap-N-Eat/api/test-setup.php
```

**What to look for:**
- ✅ All files should show "exists"
- ✅ Database connection should be "successful"
- ❌ If print_queue table missing → go to Step 3
- ✅ If everything shows ✅ → go to Step 4

---

### STEP 3: Create Database Table

**If test-setup shows print_queue missing, run:**
```
https://qsr.catalystsolutions.eco/Tap-N-Eat/create-print-queue.php
```

**Expected output:**
```
✅ print_queue table created successfully!
```

---

### STEP 4: Test API with Debug Version

Temporarily change your frontend to use the debug version. In browser console (F12), run:

```javascript
fetch('https://qsr.catalystsolutions.eco/Tap-N-Eat/api/print-thermal-debug.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-PRINT-KEY': 'print_secret'
  },
  body: JSON.stringify({
    employee: {
      emp_name: 'Test User',
      emp_id: 'TEST123',
      site: 'Test Site',
      meal_category: 'Breakfast',
      amount: 50,
      balance: 150,
      time: '10:00:00',
      date: '2026-01-06'
    },
    transaction: {
      id: 999
    }
  })
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
```

**Expected response:**
```json
{
  "status": "success",
  "message": "Print job queued successfully",
  "qr_url": "https://..."
}
```

**If you get error**, the response will show exactly what's wrong!

---

### STEP 5: Start Polling Service

```powershell
cd D:\projects\QSR_New\Myqsr\local-printer
$env:PRINTER_IP="192.168.0.105"
$env:API_KEY="print_secret"
$env:HOSTINGER_API="https://qsr.catalystsolutions.eco/Tap-N-Eat/api/print-queue.php"
node server-polling.cjs
```

**Expected output:**
```
🟢 Polling-based printer service started
🖨️  Target printer: 192.168.0.105:9100
📡 Polling: https://qsr.catalystsolutions.eco/Tap-N-Eat/api/print-queue.php
⏱️  Poll interval: 2000ms
📡 Polling for print jobs...
✅ Found 1 print job(s)
🖨️  Printing job #999 for Test User
```

---

### STEP 6: Test RFID Scan

1. Scan RFID card
2. Check polling service terminal
3. Receipt should print!

---

## 🐛 Troubleshooting

### "500 Internal Server Error"
**Cause:** Files not uploaded OR print_queue table doesn't exist
**Fix:** 
1. Check test-setup.php (Step 2)
2. Run create-print-queue.php (Step 3)
3. Check Hostinger error logs in cPanel

### "404 Not Found"
**Cause:** Files not in correct location
**Fix:** Verify file paths match exactly (case-sensitive!)

### "Unexpected end of JSON input"
**Cause:** PHP file returning empty response (fatal error)
**Fix:** Use print-thermal-debug.php to see actual error message

### "Failed to queue print job"
**Cause:** Database insert failed
**Fix:** Check if table exists, verify column names

---

## 📝 Quick Verification Commands

**Check if files uploaded:**
```
https://qsr.catalystsolutions.eco/Tap-N-Eat/api/test-setup.php
```

**Check polling service:**
```powershell
curl.exe http://localhost:8080/health
```

**Check printer:**
```powershell
Test-NetConnection -ComputerName 192.168.0.105 -Port 9100
```

---

## ✅ Success Indicators

You'll know it's working when:
1. test-setup.php shows all ✅
2. Debug API returns `{"status":"success"}`
3. Polling service shows "Found X print job(s)"
4. Receipt prints automatically
5. QR code opens transaction details

---

**Start with STEP 1 - upload the files!**
