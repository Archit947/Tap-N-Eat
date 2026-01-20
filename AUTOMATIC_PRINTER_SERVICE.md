# 🎉 AUTOMATIC PRINTER SERVICE - SETUP COMPLETE!

## ✅ What's Working Now

Your QSR printer service is now running automatically in the background using PM2!

- ✅ Service runs in background (no window needed)
- ✅ Auto-restarts if it crashes  
- ✅ Polls Hostinger every 2 seconds for print jobs
- ✅ Prints receipts automatically when RFID is scanned
- ✅ QR codes work properly

---

## 📋 Service Management Commands

Open PowerShell and use these commands:

### Check Service Status
```powershell
pm2 status
```

### View Live Logs
```powershell
pm2 logs qsr-printer
```
Press `Ctrl+C` to exit logs

### View Last 50 Log Lines
```powershell
pm2 logs qsr-printer --lines 50 --nostream
```

### Stop Service
```powershell
pm2 stop qsr-printer
```

### Start Service
```powershell
pm2 start qsr-printer
```

### Restart Service
```powershell
pm2 restart qsr-printer
```

### Delete Service (if needed)
```powershell
pm2 delete qsr-printer
```

---

## 🚀 Auto-Start on Windows Boot

### Option 1: Task Scheduler (Recommended)

1. Press `Win + R`, type `taskschd.msc`, press Enter
2. Click "Create Task" (right sidebar)
3. **General Tab:**
   - Name: `QSR Printer Service`
   - Run whether user is logged on or not: ✅
   - Run with highest privileges: ✅
4. **Triggers Tab:**
   - Click "New"
   - Begin the task: `At startup`
   - Delay task for: `30 seconds` (wait for network)
   - Click OK
5. **Actions Tab:**
   - Click "New"
   - Action: `Start a program`
   - Program: `C:\Users\ARCHIT\AppData\Roaming\npm\pm2.cmd`
   - Arguments: `resurrect`
   - Click OK
6. **Conditions Tab:**
   - Uncheck "Start the task only if the computer is on AC power"
7. Click OK, enter your Windows password

### Option 2: Startup Folder (Simple)

1. Press `Win + R`, type `shell:startup`, press Enter
2. Create shortcut to: `D:\projects\QSR_New\Myqsr\local-printer\start-printer-service.bat`
3. Service will start when you log in

---

## 🧪 Testing

1. **Scan RFID card** on your website
2. Website shows: `✅ Sent to thermal printer`
3. Within 2 seconds: **Receipt prints automatically!**

### View What Happened
```powershell
pm2 logs qsr-printer --lines 20 --nostream
```

You should see:
```
✅ Found 1 print job(s)
🖨️  Printing job #X for [Employee Name]
📟 Connected to printer 192.168.0.105:9100
✅ Job #X completed successfully
```

---

## 🔧 Configuration

Config file location:
```
D:\projects\QSR_New\Myqsr\local-printer\ecosystem.config.cjs
```

Current settings:
- **Printer IP:** 192.168.0.105
- **Printer Port:** 9100
- **API URL:** https://qsr.catalystsolutions.eco/Tap-N-Eat/api/print-queue.php
- **Poll Interval:** 2 seconds
- **API Key:** print_secret

To change settings:
1. Edit `ecosystem.config.cjs`
2. Run: `pm2 restart qsr-printer`

---

## 📊 Monitoring

### Real-time Dashboard
```powershell
pm2 monit
```
Shows CPU, memory, logs in real-time. Press `Ctrl+C` to exit.

### Service Info
```powershell
pm2 info qsr-printer
```

### Check Uptime
```powershell
pm2 status
```

---

## 🐛 Troubleshooting

### Service Not Running?
```powershell
cd D:\projects\QSR_New\Myqsr\local-printer
pm2 start ecosystem.config.cjs
```

### Check for Errors
```powershell
pm2 logs qsr-printer --err --lines 50
```

### Printer Not Responding?
```powershell
Test-NetConnection -ComputerName 192.168.0.105 -Port 9100
```

### Reset Everything
```powershell
pm2 delete qsr-printer
pm2 start ecosystem.config.cjs
pm2 save
```

---

## 📁 Important Files

| File | Location | Purpose |
|------|----------|---------|
| Service Script | `local-printer/server-polling.cjs` | Main polling service |
| PM2 Config | `local-printer/ecosystem.config.cjs` | Service configuration |
| Startup Script | `local-printer/start-printer-service.bat` | Windows startup helper |
| Logs | `local-printer/logs/` | Service logs (auto-created) |
| PM2 Data | `C:\Users\ARCHIT\.pm2\` | PM2 configuration & state |

---

## ✅ Daily Usage

**You don't need to do anything!** 

1. PC boots → Service auto-starts (if Task Scheduler configured)
2. Scan RFID → Automatic printing
3. Service runs 24/7 in background

No manual intervention needed! 🎉

---

## 📞 Quick Reference

**Check if running:** `pm2 status`  
**View logs:** `pm2 logs qsr-printer`  
**Restart:** `pm2 restart qsr-printer`  
**Stop:** `pm2 stop qsr-printer`  
**Start:** `pm2 start qsr-printer`

---

**Your QSR automatic printing system is now complete and running! 🚀**
