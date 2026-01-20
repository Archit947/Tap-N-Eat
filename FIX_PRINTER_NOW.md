# ⚠️ Printer Connection Issue - Quick Fix

Your printer at `192.168.0.1:9100` is not responding.

## 🎯 What You Need To Do NOW

### Step 1: Find Your Printer's Real IP Address (5 min)

**Easiest way - Get Network Status from Printer:**
1. Make sure printer is ON
2. Hold the **FEED** button for 3 seconds
3. Printer will print a page with network info
4. Look for line: **IP Address:** XXX.XXX.XXX.XXX
5. **Write down that IP exactly**

**Alternative - From Printer Menu:**
1. Press **MENU** on printer
2. Go to: Settings → Network → TCP/IP
3. Look for **IP Address**
4. Write it down

### Step 2: Update Your Configuration

Edit this file: **`backend/.env`**

Find:
```
PRINTER_IP=192.168.0.1
```

Change to (use your printer's IP from Step 1):
```
PRINTER_IP=192.168.X.X
```

### Step 3: Verify Connection

Open **Command Prompt** and run:
```
ping 192.168.X.X
```

Replace X.X with your actual IP from Step 1.

**Good:** You see reply messages
**Bad:** "Request timed out" - wrong IP or printer offline

### Step 4: Restart and Test

1. Save `.env` file
2. Restart PHP server:
   ```
   Stop current server (Ctrl+C)
   cd backend
   php -S localhost:8000
   ```
3. Try scanning an RFID card in Admin Dashboard
4. Watch printer output

---

## 🧪 Test Tools Available

**Diagnostic Page:**
```
http://localhost:8000/api/printer-diagnostic.php
```

**Test Print:**
```
POST: http://localhost:8000/api/printer-test.php?action=print
```

---

## ❓ Why This Happened

Your `.env` has `192.168.0.1` but your printer's actual IP is different.
Every printer gets a unique IP when connected to network - you need to find yours.

---

## ✅ Checklist

- [ ] Printer is powered on
- [ ] Found printer's IP address
- [ ] Updated `backend/.env`
- [ ] Can ping printer from Command Prompt
- [ ] Restarted PHP server
- [ ] Tried scanning RFID card

**Once all checked: Printer should work!** 🖨️
