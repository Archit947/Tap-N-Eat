# ✅ Automatic Fallback Printing Enabled

Your system now has **dual-mode printing**:

## 🖨️ How It Works

### Mode 1: Network Thermal Printer (Preferred)
- Direct connection to Epson TM-T20II via LAN
- Automatic ESC/POS formatting
- Fastest option (0-2 seconds)

**Requirements:**
- Correct printer IP in `backend/.env`
- Printer must be reachable on network
- Port 9100 must be accessible

### Mode 2: Browser Printing (Fallback)
- Prints using your computer's default printer
- Works if network printer is unavailable
- Automatic QR code generation

**When it triggers:**
- Network printer connection fails
- Wrong printer IP configured
- Printer is offline
- Port 9100 is blocked/unavailable

---

## 🔧 Configuration

### Current Setting
**File: `backend/.env`**
```
PRINTER_IP=192.168.0.105
PRINTER_PORT=9100
```

### To Find Your Printer's Actual IP:

1. **On Printer:**
   - Hold FEED button 3 seconds
   - Prints network status page
   - Look for: IP Address

2. **From Router:**
   - Open: 192.168.0.1 (or 192.168.1.1)
   - Look for connected devices
   - Find Epson printer

3. **Windows Command:**
   ```
   ping 192.168.0.105
   ```

### Update Configuration

Edit `backend/.env` and change:
```
PRINTER_IP=192.168.0.XXX  (your actual printer IP)
```

Then restart PHP server:
```
Stop current (Ctrl+C)
php -S localhost:8000
```

---

## 📊 What Gets Printed

### Network Printer:
✅ Text formatted for 58mm thermal paper
✅ QR code (embedded in ESC/POS format)
✅ Auto-cut receipt
✅ Compact 200-400mm height

### Browser Printer:
✅ Full receipt with all details
✅ QR code generated as canvas
✅ Scanned to default printer
✅ Printable on any device

---

## 🧪 Testing

1. **Test Network Printer:**
   ```
   Scan RFID → Should print to thermal printer
   Check: Is QR code visible?
   ```

2. **Test Fallback Mode:**
   - Power off printer or use wrong IP
   - Scan RFID → Browser print dialog opens
   - Print to your computer printer

3. **Test QR Code:**
   - Scan printed QR code
   - Should open: http://localhost:8000/receipt.php?id=X
   - Should show receipt details

---

## ⚙️ How to Switch Modes

### Force Network Printer Only
Edit `backend/.env`:
```
PRINTER_IP=192.168.0.105
PRINTER_PORT=9100
# (no changes needed - default)
```

### Force Browser Printer Only
Edit `backend/.env`:
```
PRINTER_IP=127.0.0.1
PRINTER_PORT=9999
# (invalid IP forces fallback)
```

---

## 📋 Troubleshooting

### Symptom: Browser print opening instead of thermal
**Solution:** 
1. Check printer IP is correct: `ping 192.168.0.105`
2. Verify port 9100 is open: `telnet 192.168.0.105 9100`
3. Check printer power and network cable
4. Update `.env` with correct IP

### Symptom: Printing but no QR code
**Network:** Check printer support for QR barcodes
**Browser:** Ensure JavaScript enabled in print preview

### Symptom: Timeout errors
**Solution:**
- Check network connectivity
- Verify firewall isn't blocking port 9100
- Try fallback mode (power off printer)

---

## 🎯 Recommended Setup

1. **Get printer IP:**
   - Hold FEED on printer 3 seconds
   - Note the IP address

2. **Update `.env`:**
   - Set PRINTER_IP to actual value
   - Keep PRINTER_PORT as 9100

3. **Test connection:**
   - Ping printer
   - Scan RFID card
   - Verify output (network or browser)

4. **Deploy:**
   - System automatically switches between modes
   - No downtime if printer goes offline
   - Always prints (network or browser)

---

## 📞 Support

**Network Printer Isn't Responding:**
- Run: `ping 192.168.0.105`
- Check printer display for IP
- Verify network cable connected
- Check router/firewall settings

**Browser Printing Issues:**
- Ensure default printer configured
- Check paper supply
- Verify print drivers installed

**QR Code Problems:**
- Thermal: Check printer manual for QR support
- Browser: Ensure sufficient print area
- Both: Verify correct URL in QR (receipt.php?id=X)

---

✅ **Your system is now resilient and flexible!**
- Works with network printer when available
- Falls back to browser printing automatically
- No manual intervention needed
