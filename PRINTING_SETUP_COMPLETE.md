# COMPLETE PRINTING SOLUTION - READY TO USE

## Problem Solved ✅
1. ✅ No tunneling needed (ngrok/localtunnel)
2. ✅ No port forwarding needed
3. ✅ No firewall configuration needed
4. ✅ Works automatically with RFID scan
5. ✅ QR codes work properly

## How It Works

### Architecture:
```
RFID Scan → Hostinger (saves to print_queue) → Local PC polls Hostinger → Prints receipt
```

**Polling Method**: Your local PC checks Hostinger every 2 seconds for new print jobs. This is better because:
- PC can always reach Hostinger (outbound connection)
- No need for public IP or tunnel
- Simple and reliable

---

## STEP-BY-STEP SETUP

### STEP 1: Upload Files to Hostinger

Upload these 4 files via FileZilla/cPanel File Manager:

| Local File | Upload To Hostinger |
|------------|---------------------|
| `backend/config/database.php` | `/public_html/Tap-N-Eat/config/database.php` |
| `backend/create-print-queue.php` | `/public_html/Tap-N-Eat/create-print-queue.php` |
| `backend/api/print-queue.php` | `/public_html/Tap-N-Eat/api/print-queue.php` |
| `backend/api/print-thermal.php` | `/public_html/Tap-N-Eat/api/print-thermal.php` |

---

### STEP 2: Create Database Table

Open this URL in your browser **ONCE**:
```
https://qsr.catalystsolutions.eco/Tap-N-Eat/create-print-queue.php
```

**Expected output:**
```
✅ print_queue table created successfully!
```

---

### STEP 3: Start Local Polling Service

Open **PowerShell** on your PC and run:

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
🔑 API Key: print_secret
📡 Polling for print jobs...
```

**Keep this window open!** The service must stay running for automatic printing.

---

### STEP 4: Test Everything

1. **Scan RFID card** on your website
2. **Watch the PowerShell window** - you should see:
   ```
   ✅ Found 1 print job(s)
   🖨️  Printing job #1 for Employee Name
   📟 Connected to printer 192.168.0.105:9100
 
3. **Receipt prints automatically!**
4. **QR code** on the receipt works (opens transaction details)

---

## Troubleshooting

### Issue: Polling service shows "❌ API error: 404"
**Fix:** You haven't uploaded the files yet. Go back to Step 1.

**Fix:** 
1. Check printer is ON and connected to network
2. Verify IP: `Test-NetConnection -ComputerName 192.168.0.105 -Port 9100`
3. Make sure printer is on same network or routed properly

### Issue: QR code doesn't scan/open
**Fix:** Make sure `receipt.php` exists on Hostinger at:
```
/public_html/Tap-N-Eat/receipt.php
```

### Issue: No print after scanning RFID
**Checks:**
1. ✅ Polling service running? (check PowerShell window)
2. ✅ Files uploaded to Hostinger?
3. ✅ Database table created?
4. ✅ Transaction successful on website?
5. ✅ Check polling service logs for errors

---

## Running the Service Automatically (Optional)

### To run polling service on PC startup:

1. Create file: `C:\Users\ARCHIT\start-print-service.bat`
2. Contents:
```batch
@echo off
cd D:\projects\QSR_New\Myqsr\local-printer
set PRINTER_IP=192.168.0.105
set API_KEY=print_secret
set HOSTINGER_API=https://qsr.catalystsolutions.eco/Tap-N-Eat/api/print-queue.php
node server-polling.cjs
pause
```
3. Create shortcut in `shell:startup` folder
4. Service starts automatically when PC boots

---

## Files Summary

### Files Already On Your PC ✅
- `D:\projects\QSR_New\Myqsr\backend\config\database.php` (updated)
- `D:\projects\QSR_New\Myqsr\backend\create-print-queue.php` (new)
- `D:\projects\QSR_New\Myqsr\backend\api\print-queue.php` (new)
- `D:\projects\QSR_New\Myqsr\backend\api\print-thermal.php` (updated)
- `D:\projects\QSR_New\Myqsr\local-printer\server-polling.cjs` (new)

### What Changed ✅
1. **database.php**: Added `getConnection()` helper function
2. **print-thermal.php**: Changed from direct printing to queue-based (adds job to database)
3. **print-queue.php**: NEW - API for polling service to get/update jobs
4. **create-print-queue.php**: NEW - Creates `print_queue` table
5. **server-polling.cjs**: NEW - Polls Hostinger and prints receipts

---

## System Requirements
- ✅ Node.js installed (already have)
- ✅ Printer: Epson TM-T20III on 192.168.0.105:9100
- ✅ Internet connection for PC
- ✅ MySQL database on Hostinger

---

## Support
If you need help, check:
1. PowerShell terminal logs (polling service)
2. Hostinger error logs (cPanel → Error Logs)
3. Browser console (F12) on RFID scan page
4. Printer status (lights/display)

---

**Ready to go! Follow Step 1 to upload files now! 🚀**
