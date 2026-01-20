import { useState, useEffect } from 'react';
import './AdminDashboard.css';
import './WalletSummary.css';
import catalystLogo from '../assets/logo.webp';

// Resolve API base dynamically so it works on Hostinger (/qsr/) and locally
const API_BASE_URL = (() => {
  const envBase = import.meta.env.VITE_API_BASE_URL;
  if (envBase) return envBase.replace(/\/$/, '');
  const path = window.location.pathname;
  // Hostinger path like /qsr/Tap-N-Eat/frontend/
  if (path.includes('/qsr/') && path.includes('/Tap-N-Eat/frontend')) {
    return '/qsr/Tap-N-Eat/api';
  }
  // Direct path like /Tap-N-Eat/frontend/
  if (path.includes('/Tap-N-Eat/frontend')) {
    return '/Tap-N-Eat/api';
  }
  // If backend is deployed under /Tap-N-Eat/backend/api
  if (path.includes('/Tap-N-Eat/frontend') && !path.includes('/qsr/')) {
    return '/Tap-N-Eat/backend/api';
  }
  if (path.includes('/qsr/') && path.includes('/Tap-N-Eat/frontend')) {
    return '/qsr/Tap-N-Eat/backend/api';
  }
  // Default local/backend fallback
  if (path.includes('/qsr/')) return '/qsr/backend/api';
  return '/backend/api';
})();

function AdminDashboard() {
  const [role] = useState(() => {
    try {
      return localStorage.getItem('adminRole') || '';
    } catch {
      return '';
    }
  });
  const isSecurity = role === 'security';
  const isReadOnly = role === 'hr' || isSecurity;

  const allowedSections = isSecurity
    ? ['employees', 'scan']
    : ['dashboard', 'employees', 'lookup', 'wallet', 'scan', 'transactions'];
  const defaultSection = allowedSections[0] || 'employees';

  useEffect(() => {
    if (!role) {
      window.location.hash = '#/admin-login';
    }
  }, [role]);

  const [employees, setEmployees] = useState([]);
  const [loading, setLoading] = useState(false);
  const [alert, setAlert] = useState({ show: false, message: '', type: '' });
  const [showEditModal, setShowEditModal] = useState(false);
  const ACTIVE_SECTION_KEY = 'adminActiveSection';
  const [activeSection, setActiveSectionState] = useState(() => {
    try {
      const stored = localStorage.getItem(ACTIVE_SECTION_KEY);
      return stored && allowedSections.includes(stored) ? stored : defaultSection;
    } catch {
      return defaultSection;
    }
  });
  const setActiveSection = (section) => {
    const next = allowedSections.includes(section) ? section : defaultSection;
    setActiveSectionState(next);
    try {
      localStorage.setItem(ACTIVE_SECTION_KEY, next);
    } catch {
      // ignore
    }
  };
  const [walletMode, setWalletMode] = useState('single');
  const [showRechargeForm, setShowRechargeForm] = useState(false);

  // Dashboard states
  const [dashboardDate, setDashboardDate] = useState(() => new Date().toISOString().split('T')[0]);
  // Empty string means "no filter" (all meals)
  const [dashboardMealFilter, setDashboardMealFilter] = useState('Lunch');
  const [dashboardSearch, setDashboardSearch] = useState('');
  const [trendRange, setTrendRange] = useState('week');
  
  // Wallet Recharge States
  const [searchQuery, setSearchQuery] = useState('');
  const [searchedEmployee, setSearchedEmployee] = useState(null);
  const [rechargeAmount, setRechargeAmount] = useState('');
  const [bulkRechargeAmount, setBulkRechargeAmount] = useState('');
  
  // RFID Scan States
  const [rfidInput, setRfidInput] = useState('');
  const [scanLoading, setScanLoading] = useState(false);
  const [lastTransaction, setLastTransaction] = useState(null);
  const [scannedEmployee, setScannedEmployee] = useState(null);
  const [currentMealInfo, setCurrentMealInfo] = useState(null);
  
  // Transaction History States
  const [transactions, setTransactions] = useState([]);
  const [transactionFilter, setTransactionFilter] = useState({
    date: new Date().toISOString().split('T')[0],
    mealCategory: ''
  });
  
  const [formData, setFormData] = useState({
    rfid_number: '',
    emp_id: '',
    emp_name: '',
    site_name: '',
    shift: '',
    wallet_amount: '0.00'
  });

  const [employeeSearch, setEmployeeSearch] = useState('');

  // Employee Lookup (search by ID/RFID/Name)
  const [lookupTerm, setLookupTerm] = useState('');
  const [lookupSelectedId, setLookupSelectedId] = useState(null);
  const [lookupTransactions, setLookupTransactions] = useState([]);

  useEffect(() => {
    if (lookupSelectedId) {
      loadLookupTransactions(lookupSelectedId);
    } else {
      setLookupTransactions([]);
    }
  }, [lookupSelectedId]);

  const loadLookupTransactions = async (empId) => {
    try {
      const response = await fetch(`${API_BASE_URL}/transactions.php?employee_id=${empId}&limit=500`);
      const data = await response.json();
      if (response.ok) {
        setLookupTransactions(data.transactions || []);
      }
    } catch (error) {
      console.error('Error fetching lookup transactions:', error);
    }
  };

  const [editData, setEditData] = useState({
    id: '',
    rfid_number: '',
    emp_id: '',
    emp_name: '',
    site_name: '',
    shift: '',
    wallet_amount: '0.00'
  });

  const normalize = (v) => String(v ?? '').trim().toLowerCase();

  // Derived state for Employee Lookup
  const lookupMatches = (() => {
    if (activeSection !== 'lookup') return [];
    const q = normalize(lookupTerm);
    if (!q) return [];
    
    return employees
      .filter((e) => {
        const empId = normalize(e.emp_id);
        const rfid = normalize(e.rfid_number);
        const name = normalize(e.emp_name);
        return empId.includes(q) || rfid.includes(q) || name.includes(q);
      })
      .slice(0, 20);
  })();

  const lookupSelected = (() => {
    if (activeSection !== 'lookup') return null;
    // 1. If explicit ID selected, return that
    if (lookupSelectedId) {
      return employees.find((e) => String(e.id) === String(lookupSelectedId));
    }
    // 2. If exactly one match, return that automatically
    if (lookupMatches.length === 1) {
      return lookupMatches[0];
    }
    return null;
  })();

  useEffect(() => {
    if (lookupSelected && lookupSelected.id) {
      loadLookupTransactions(lookupSelected.id);
    } else {
      setLookupTransactions([]);
    }
  }, [lookupSelected?.id, activeSection]);

  useEffect(() => {
    loadEmployees();
    loadCurrentMealInfo();
    if (activeSection === 'transactions' || activeSection === 'dashboard') {
      loadTransactions({ bypassFilters: activeSection === 'dashboard' });
    }
  }, [activeSection]);

  useEffect(() => {
    try {
      localStorage.setItem(ACTIVE_SECTION_KEY, activeSection);
    } catch {
      // ignore
    }
  }, [activeSection]);

  useEffect(() => {
    if (!allowedSections.includes(activeSection)) {
      setActiveSection(defaultSection);
    }
  }, [role]);

  useEffect(() => {
    if (activeSection === 'wallet') {
      loadTransactions({ bypassFilters: true });
    }
  }, [activeSection]);
  
  useEffect(() => {
    if (activeSection === 'transactions') {
      loadTransactions();
    }
  }, [transactionFilter]);

  const loadEmployees = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${API_BASE_URL}/employees.php`);
      const data = await response.json();
      setEmployees(Array.isArray(data) ? data : []);
      setLoading(false);
    } catch (error) {
      console.error('Error loading employees:', error);
      showAlert('Error loading employees', 'error');
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleEditInputChange = (e) => {
    const { name, value } = e.target;
    setEditData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (isReadOnly) return showAlert('Read-only role: cannot add employees', 'warning');
    
    try {
      const response = await fetch(`${API_BASE_URL}/employees.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      const result = await response.json();

      if (response.ok) {
        showAlert('Employee added successfully!', 'success');
        resetForm();
        loadEmployees();
      } else {
        showAlert(result.message || 'Error adding employee', 'error');
      }
    } catch (error) {
      console.error('Error creating employee:', error);
      showAlert('Error adding employee', 'error');
    }
  };

  const handleEditSubmit = async (e) => {
    e.preventDefault();
    if (isReadOnly) return showAlert('Read-only role: cannot edit employees', 'warning');
    
    try {
      const response = await fetch(`${API_BASE_URL}/employees.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(editData)
      });

      const result = await response.json();

      if (response.ok) {
        showAlert('Employee updated successfully!', 'success');
        setShowEditModal(false);
        loadEmployees();
      } else {
        showAlert(result.message || 'Error updating employee', 'error');
      }
    } catch (error) {
      console.error('Error updating employee:', error);
      showAlert('Error updating employee', 'error');
    }
  };

  const editEmployee = async (id) => {
    try {
      const response = await fetch(`${API_BASE_URL}/employees.php?id=${id}`);
      const employee = await response.json();

      if (response.ok) {
        setEditData({
          id: employee.id,
          rfid_number: employee.rfid_number,
          emp_id: employee.emp_id,
          emp_name: employee.emp_name,
          site_name: employee.site_name,
          shift: employee.shift,
          wallet_amount: employee.wallet_amount || '0.00'
        });
        setShowEditModal(true);
      } else {
        showAlert('Error loading employee data', 'error');
      }
    } catch (error) {
      console.error('Error loading employee:', error);
      showAlert('Error loading employee data', 'error');
    }
  };

  const deleteEmployee = async (id) => {
    if (isReadOnly) return showAlert('Read-only role: cannot delete employees', 'warning');
    if (!window.confirm('Are you sure you want to delete this employee?')) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/employees.php`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
      });

      const result = await response.json();

      if (response.ok) {
        showAlert('Employee deleted successfully!', 'success');
        loadEmployees();
      } else {
        showAlert(result.message || 'Error deleting employee', 'error');
      }
    } catch (error) {
      console.error('Error deleting employee:', error);
      showAlert('Error deleting employee', 'error');
    }
  };

  const resetForm = () => {
    setFormData({
      rfid_number: '',
      emp_id: '',
      emp_name: '',
      site_name: '',
      shift: '',
      wallet_amount: '0.00'
    });
  };

  const handleLookupChange = (e) => {
    setLookupTerm(e.target.value);
    setLookupSelectedId(null);
  };

  const showAlert = (message, type) => {
    setAlert({ show: true, message, type });
    setTimeout(() => {
      setAlert({ show: false, message: '', type: '' });
    }, 5000);
  };

  const getInitials = (fullName) => {
    const cleaned = String(fullName || '').trim();
    if (!cleaned) return '—';
    const parts = cleaned.split(/\s+/).filter(Boolean);
    const first = parts[0]?.[0] || '';
    const last = parts.length > 1 ? parts[parts.length - 1]?.[0] : '';
    const initials = (first + last).toUpperCase();
    return initials || '—';
  };

  // Search Employee by RFID or Employee ID
  const searchEmployee = async () => {
    if (!searchQuery.trim()) {
      showAlert('Please enter RFID or Employee ID', 'error');
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/wallet-recharge.php?search=${encodeURIComponent(searchQuery)}`);
      const data = await response.json();

      if (response.ok) {
        setSearchedEmployee(data);
        setRechargeAmount('');
        setShowRechargeForm(false);
        showAlert('Employee found!', 'success');
      } else {
        setSearchedEmployee(null);
        setShowRechargeForm(false);
        showAlert(data.message || 'Employee not found', 'error');
      }
    } catch (error) {
      console.error('Error searching employee:', error);
      showAlert('Error searching employee', 'error');
    }
  };

  // Recharge Individual Employee
  const rechargeIndividualWallet = async () => {
    if (isReadOnly) {
      showAlert('Read-only role: cannot recharge wallets', 'warning');
      return;
    }
    if (!searchedEmployee) {
      showAlert('Please search for an employee first', 'error');
      return;
    }

    if (!rechargeAmount || parseFloat(rechargeAmount) <= 0) {
      showAlert('Please enter a valid amount', 'error');
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/wallet-recharge.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          employee_id: searchedEmployee.id,
          amount: parseFloat(rechargeAmount)
        })
      });

      const result = await response.json();

      if (response.ok) {
        showAlert(`₹${rechargeAmount} added to ${searchedEmployee.emp_name}'s wallet!`, 'success');
        setSearchedEmployee(result.employee);
        setRechargeAmount('');
        loadEmployees(); // Refresh employee list
      } else {
        showAlert(result.message || 'Error recharging wallet', 'error');
      }
    } catch (error) {
      console.error('Error recharging wallet:', error);
      showAlert('Error recharging wallet', 'error');
    }
  };

  // Bulk Recharge All Employees
  const bulkRechargeWallets = async () => {
    if (isReadOnly) {
      showAlert('Read-only role: cannot recharge wallets', 'warning');
      return;
    }
    if (!bulkRechargeAmount || parseFloat(bulkRechargeAmount) <= 0) {
      showAlert('Please enter a valid amount for bulk recharge', 'error');
      return;
    }

    if (!window.confirm(`Are you sure you want to add ₹${bulkRechargeAmount} to ALL employees' wallets?`)) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/wallet-recharge.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          bulk_recharge: true,
          amount: parseFloat(bulkRechargeAmount)
        })
      });

      const result = await response.json();

      if (response.ok) {
        showAlert(`₹${bulkRechargeAmount} added to ${result.employees_recharged} employees!`, 'success');
        setBulkRechargeAmount('');
        loadEmployees(); // Refresh employee list
      } else {
        showAlert(result.message || 'Error performing bulk recharge', 'error');
      }
    } catch (error) {
      console.error('Error performing bulk recharge:', error);
      showAlert('Error performing bulk recharge', 'error');
    }
  };
  
 
  
  // RFID Scan for Meal Deduction
  const loadCurrentMealInfo = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/rfid-scan.php`);
      const data = await response.json();
      setCurrentMealInfo(data.meal_info);
    } catch (error) {
      console.error('Error loading meal info:', error);
    }
  };
  
  const handleRfidScan = async (e) => {
    if (e) {
      e.preventDefault();
    }
    
    if (!rfidInput.trim()) {
      showAlert('Please enter RFID number', 'error');
      return;
    }
    
    // Store the RFID and clear immediately
    const scannedRfid = rfidInput.trim();
    setRfidInput('');
    setScanLoading(true);
    
    try {
      const response = await fetch(`${API_BASE_URL}/rfid-scan.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          rfid_number: scannedRfid
        })
      });
      
      const result = await response.json();
      
      if (response.ok) {
        const employeeName = result.employee?.name || result.employee?.emp_name || 'employee';
        setLastTransaction({ ...result.transaction, employee: result.employee });
        setScannedEmployee(result.employee || null);
        showAlert(`✅ ${result.transaction.meal_category} - ₹${result.transaction.amount_deducted} deducted from ${employeeName}'s wallet`, 'success');
        
        loadEmployees();
        
        // Send to thermal printer (network)
        setTimeout(() => {
          printToThermalPrinter(result.employee, result.transaction);
        }, 500);
        
        // Refocus the input field after a short delay
        setTimeout(() => {
          const rfidInputElement = document.getElementById('rfidInput');
          if (rfidInputElement) {
            rfidInputElement.focus();
            rfidInputElement.select();
          }
        }, 100);
      } else {
        showAlert(result.message || 'Transaction failed', 'error');
        setLastTransaction(null);
        setScannedEmployee(null);
        // Refocus on error too
        setTimeout(() => {
          const rfidInputElement = document.getElementById('rfidInput');
          if (rfidInputElement) {
            rfidInputElement.focus();
            rfidInputElement.select();
          }
        }, 100);
      }
    } catch (error) {
      console.error('Error scanning RFID:', error);
      showAlert('Error processing scan', 'error');
      setScannedEmployee(null);
      setLastTransaction(null);
    } finally {
      setScanLoading(false);
    }
  };

  const clearScanState = () => {
    setLastTransaction(null);
    setScannedEmployee(null);
    setRfidInput('');
  };
  
 const printToThermalPrinter = async (employee, transaction) => {
    try {
      const response = await fetch(`${API_BASE_URL}/print-thermal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-PRINT-KEY': 'print_secret'
        },
        body: JSON.stringify({
          employee: {
            emp_name: employee.name,
            emp_id: employee.emp_id,
            site: employee.site,
            meal_category: transaction.meal_category,
            amount: transaction.amount_deducted,
            balance: transaction.new_balance,
            time: transaction.time,
            date: transaction.date
          },
          transaction: {
            id: transaction.id
          }
        })
      });

      const result = await response.json();
      
      if (response.ok || result.status === 'success') {
        // Check if we're using network printer or backend-reported mode
        if (result.mode === 'network') {
          showAlert('✅ Sent to thermal printer', 'success');
          // Clear transaction after successful network print
          setTimeout(() => {
            setLastTransaction(null);
          }, 2000);
        } else {
          showAlert('⚠️ Printer unavailable', 'warning');
        }
      } else {
        console.error('Printer error:', result.message);
        showAlert(`⚠️ ${result.message}`, 'warning');
      }
    } catch (error) {
      console.error('Error sending to printer:', error);
      showAlert('⚠️ Printer request failed', 'warning');
    }
  };

  
  // Load Transactions
  const loadTransactions = async ({ bypassFilters = false } = {}) => {
    try {
      setLoading(true);
      let url = `${API_BASE_URL}/transactions.php?limit=100`;
      
      if (!bypassFilters) {
        if (transactionFilter.date) {
          url += `&date=${transactionFilter.date}`;
        }
        if (transactionFilter.mealCategory) {
          url += `&meal_category=${transactionFilter.mealCategory}`;
        }
      }
      
      const response = await fetch(url);
      const data = await response.json();
      
      if (response.ok) {
        setTransactions(data.transactions || []);
      }
      setLoading(false);
    } catch (error) {
      console.error('Error loading transactions:', error);
      showAlert('Error loading transactions', 'error');
      setLoading(false);
    }
  };

  // Export transactions to print-friendly PDF (via browser print-to-PDF)
  const exportTransactionsPDF = () => {
    if (!transactions.length) {
      showAlert('No transactions to export', 'error');
      return;
    }

    const title = 'Transaction History';
    const dateStr = new Date().toLocaleString();
    const rows = transactions
      .map((t) => `
        <tr>
          <td>${t.transaction_date}</td>
          <td>${t.transaction_time}</td>
          <td>${t.emp_name || 'Visitor'} (${t.emp_id || 'VIS'})</td>
          <td>${t.rfid_number || ''}</td>
          <td>${t.meal_category || (t.transaction_type === 'visitor' ? 'Visitor Order' : 'Recharge')}</td>
          <td>${t.transaction_type === 'deduction' ? '-' : '+'}₹${parseFloat(t.amount || 0).toFixed(2)}</td>
          <td>${t.previous_balance ? '₹'+parseFloat(t.previous_balance).toFixed(2) : '—'}</td>
          <td>${t.new_balance ? '₹'+parseFloat(t.new_balance).toFixed(2) : '—'}</td>
          <td>${t.site_name || ''}</td>
          <td>${t.order_status || 'Pending'}</td>
        </tr>
      `)
      .join('');

    const win = window.open('', '_blank', 'width=900,height=700');
    if (!win) return;

    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #1f2937; }
        h1 { margin-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 16px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        tr:nth-child(even) { background: #f9fafb; }
      </style>
    </head><body>
      <h1>${title}</h1>
      <div class="meta">Exported on ${dateStr}</div>
      <table>
        <thead>
          <tr>
            <th>Date</th><th>Time</th><th>Employee</th><th>RFID</th><th>Meal</th><th>Amount</th><th>Prev Bal</th><th>New Bal</th><th>Site</th><th>Status</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
      <script>window.print();</script>
    </body></html>`);
    win.document.close();
  };

  // Export the current Dashboard view (print to PDF)
  const exportDashboardPDF = () => {
    // Use browser Print dialog (user selects "Save as PDF")
    window.print();
  };

  // Insight calculations
  const totalEmployees = employees.length;
  const totalWallet = employees.reduce((sum, e) => sum + parseFloat(e.wallet_amount || 0), 0);
  const avgWallet = totalEmployees ? totalWallet / totalEmployees : 0;

  const deductionTx = transactions.filter((t) => t.transaction_type === 'deduction');
  const rechargeTx = transactions.filter((t) => t.transaction_type !== 'deduction');

  const todayIso = new Date().toISOString().split('T')[0];
  const dashboardIso = dashboardDate || todayIso;
  const spendToday = deductionTx
    .filter(
      (t) =>
        t.transaction_date === dashboardIso &&
        (!dashboardMealFilter || t.meal_category === dashboardMealFilter)
    )
    .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);

  const parseIsoDate = (iso) => {
    if (!iso) return null;
    const d = new Date(`${iso}T00:00:00`);
    return Number.isNaN(d.getTime()) ? null : d;
  };

  const inRangeInclusive = (iso, start, end) => {
    const d = parseIsoDate(iso);
    if (!d || !start || !end) return false;
    return d.getTime() >= start.getTime() && d.getTime() <= end.getTime();
  };

  const getTrendWindow = () => {
    const base = parseIsoDate(dashboardIso) || new Date();
    const end = new Date(base);
    end.setHours(23, 59, 59, 999);

    if (trendRange === 'year') {
      const start = new Date(base.getFullYear(), 0, 1);
      const yearEnd = new Date(base.getFullYear(), 11, 31);
      yearEnd.setHours(23, 59, 59, 999);
      return { start, end: yearEnd };
    }

    if (trendRange === 'month') {
      const start = new Date(base.getFullYear(), base.getMonth(), 1);
      const monthEnd = new Date(base.getFullYear(), base.getMonth() + 1, 0);
      monthEnd.setHours(23, 59, 59, 999);
      return { start, end: monthEnd };
    }

    if (trendRange === 'day') {
      const start = new Date(base);
      start.setHours(0, 0, 0, 0);
      return { start, end };
    }

    const start = new Date(base);
    start.setDate(start.getDate() - 6);
    start.setHours(0, 0, 0, 0);
    return { start, end };
  };

  const trendWindow = getTrendWindow();
  const trendTx = transactions.filter((t) => inRangeInclusive(t.transaction_date, trendWindow.start, trendWindow.end));
  const trendDeductionTx = trendTx.filter((t) => t.transaction_type === 'deduction');
  const trendRechargeTx = trendTx.filter((t) => t.transaction_type !== 'deduction');

  // Meal Participation: Unique employees who had a transaction ON THE SELECTED DASHBOARD DATE
  // Filtered by meal type if selected
  const participationUnique = new Set(
    deductionTx
      .filter((t) => 
        t.transaction_date === dashboardIso && 
        (dashboardMealFilter ? t.meal_category === dashboardMealFilter : true)
      )
      .map((t) => t.emp_id || t.emp_name || t.rfid_number)
      .filter(Boolean)
  );
  const participationCount = participationUnique.size;
  const participationPct = totalEmployees ? Math.round((participationCount / totalEmployees) * 100) : 0;

  const mealCounts = ['Breakfast', 'Mid-Meal', 'Lunch', 'Dinner'].map((meal) => ({
    meal,
    count: trendDeductionTx.filter((t) => t.meal_category === meal).length,
  }));
  const mealTotal = mealCounts.reduce((s, m) => s + m.count, 0);

  const scannedBalance = Number(scannedEmployee?.wallet_amount ?? lastTransaction?.new_balance ?? 0);
  const recentRecharges = transactions
    .filter((t) => t.transaction_type !== 'deduction')
    .slice(0, 5);

  // Build conic gradient for meal distribution
  const pieStyle = (() => {
    if (!mealTotal) return { background: '#f1f5f9' };
    const colors = ['#6c5ce7', '#00b894', '#0984e3', '#ff7675'];
    let angle = 0;
    const segments = mealCounts.map((m, i) => {
      const deg = (m.count / mealTotal) * 360;
      const seg = `${colors[i % colors.length]} ${angle}deg ${angle + deg}deg`;
      angle += deg;
      return seg;
    });
    return { background: `conic-gradient(${segments.join(', ')})` };
  })();

  const buildSeries = () => {
    const base = parseIsoDate(dashboardIso) || new Date();

    if (trendRange === 'year') {
      const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const spend = Array(12).fill(0);
      const recharge = Array(12).fill(0);

      trendDeductionTx.forEach((t) => {
        const d = parseIsoDate(t.transaction_date);
        if (!d) return;
        spend[d.getMonth()] += parseFloat(t.amount || 0);
      });
      trendRechargeTx.forEach((t) => {
        const d = parseIsoDate(t.transaction_date);
        if (!d) return;
        recharge[d.getMonth()] += parseFloat(t.amount || 0);
      });

      return { labels, spend, recharge };
    }

    if (trendRange === 'month') {
      const labels = [];
      const spend = [];
      const recharge = [];

      const start = new Date(base.getFullYear(), base.getMonth(), 1);
      const end = new Date(base.getFullYear(), base.getMonth() + 1, 0);
      const dayMapSpend = new Map();
      const dayMapRecharge = new Map();

      trendDeductionTx.forEach((t) => {
        dayMapSpend.set(t.transaction_date, (dayMapSpend.get(t.transaction_date) || 0) + parseFloat(t.amount || 0));
      });
      trendRechargeTx.forEach((t) => {
        dayMapRecharge.set(t.transaction_date, (dayMapRecharge.get(t.transaction_date) || 0) + parseFloat(t.amount || 0));
      });

      for (let d = new Date(start); d.getTime() <= end.getTime(); d.setDate(d.getDate() + 1)) {
        const iso = d.toISOString().split('T')[0];
        labels.push(String(d.getDate()));
        spend.push(dayMapSpend.get(iso) || 0);
        recharge.push(dayMapRecharge.get(iso) || 0);
      }

      return { labels, spend, recharge };
    }

    if (trendRange === 'day') {
      const labels = ['00', '02', '04', '06', '08', '10', '12', '14', '16', '18', '20', '22'];
      const spend = Array(12).fill(0);
      const recharge = Array(12).fill(0);
      
      const getBucket = (t) => {
        if (!t.transaction_time) return -1;
        const h = parseInt(t.transaction_time.split(':')[0], 10);
        return Math.floor(h / 2);
      };

      trendDeductionTx.forEach((t) => {
        const b = getBucket(t);
        if (b >= 0 && b < 12) spend[b] += parseFloat(t.amount || 0);
      });
      trendRechargeTx.forEach((t) => {
        const b = getBucket(t);
        if (b >= 0 && b < 12) recharge[b] += parseFloat(t.amount || 0);
      });
      
      return { labels, spend, recharge };
    }

    // week
    const labels = [];
    const spend = [];
    const recharge = [];
    const start = new Date(base);
    start.setDate(start.getDate() - 6);
    start.setHours(0, 0, 0, 0);

    const dayMapSpend = new Map();
    const dayMapRecharge = new Map();
    trendDeductionTx.forEach((t) => {
      dayMapSpend.set(t.transaction_date, (dayMapSpend.get(t.transaction_date) || 0) + parseFloat(t.amount || 0));
    });
    trendRechargeTx.forEach((t) => {
      dayMapRecharge.set(t.transaction_date, (dayMapRecharge.get(t.transaction_date) || 0) + parseFloat(t.amount || 0));
    });

    for (let d = new Date(start); labels.length < 7; d.setDate(d.getDate() + 1)) {
      const iso = d.toISOString().split('T')[0];
      labels.push(d.toLocaleDateString(undefined, { weekday: 'short' }));
      spend.push(dayMapSpend.get(iso) || 0);
      recharge.push(dayMapRecharge.get(iso) || 0);
    }

    return { labels, spend, recharge };
  };

  const trendSeries = buildSeries();
  const maxTrend = Math.max(...trendSeries.spend, ...trendSeries.recharge, 1);
  
  // Bar Chart Calculations
  const chartH = 200;
  const chartW = 560;
  const chartPad = 20;
  const drawW = chartW - chartPad * 2;
  const drawH = chartH - chartPad * 2;
  
  const barGroups = trendSeries.labels.map((label, i) => {
    const n = trendSeries.labels.length;
    const groupWidth = drawW / n;
    const barWidth = groupWidth * 0.35; 
    const gap = groupWidth * 0.1; 
      
    const xBase = chartPad + (i * groupWidth) + (groupWidth - (barWidth * 2 + gap)) / 2;
      
    const spendVal = trendSeries.spend[i] || 0;
    const spendH = (spendVal / maxTrend) * drawH;
    const spendY = chartH - chartPad - spendH;
      
    const rechargeVal = trendSeries.recharge[i] || 0;
    const rechargeH = (rechargeVal / maxTrend) * drawH;
    const rechargeY = chartH - chartPad - rechargeH;
      
    return {
      label,
      xSpend: xBase,
      ySpend: spendY,
      hSpend: spendH,
      xRecharge: xBase + barWidth + gap,
      yRecharge: rechargeY,
      hRecharge: rechargeH,
      width: barWidth
    };
  });


  return (
    <div className="dashboard-container">
      {/* Sidebar */}
      <div className="sidebar">
        <div className="sidebar-brand">
          <img src={catalystLogo} alt="Catalyst" />
        </div>
        {!isSecurity && (
          <div 
            className={`menu-item menu-dashboard ${activeSection === 'dashboard' ? 'active' : ''}`}
            onClick={() => setActiveSection('dashboard')}
          >
            <span className="menu-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19V5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M8 19V11" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M12 19V7" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M16 19V14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path d="M20 19V9" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              </svg>
            </span>
            <span className="menu-label">Dashboard</span>
          </div>
        )}
        <div 
          className={`menu-item menu-employees ${activeSection === 'employees' ? 'active' : ''}`}
          onClick={() => setActiveSection('employees')}
        >
          <span className="menu-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M22 21v-2a3 3 0 0 0-2-2.82" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M17 3.18a4 4 0 0 1 0 7.64" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            </svg>
          </span>
          <span className="menu-label">Employees</span>
        </div>

        {!isSecurity && (
          <>
            <div
              className={`menu-item menu-lookup ${activeSection === 'lookup' ? 'active' : ''}`}
              onClick={() => setActiveSection('lookup')}
            >
              <span className="menu-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z" stroke="currentColor" strokeWidth="2" />
                  <path d="M21 21l-4.2-4.2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                  <path d="M10.5 7a3.5 3.5 0 0 1 3.5 3.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" opacity="0.7" />
                </svg>
              </span>
              <span className="menu-label">Employee Details</span>
            </div>
            <div 
              className={`menu-item menu-wallet ${activeSection === 'wallet' ? 'active' : ''}`}
              onClick={() => setActiveSection('wallet')}
            >
              <span className="menu-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke="currentColor" strokeWidth="2" />
                  <path d="M17 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" strokeWidth="2" />
                  <path d="M3 9h18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
              </span>
              <span className="menu-label">Wallet Recharge</span>
            </div>
          </>
        )}
        <div 
          className={`menu-item menu-scan ${activeSection === 'scan' ? 'active' : ''}`}
          onClick={() => setActiveSection('scan')}
        >
          <span className="menu-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8 2h8a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="currentColor" strokeWidth="2" />
              <path d="M10 19h4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M9 6h6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M7 9h10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" opacity="0.9" />
              <path d="M7 12h10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" opacity="0.75" />
            </svg>
          </span>
          <span className="menu-label">RFID Scan</span>
        </div>
        {!isSecurity && (
          <>
            <div 
              className={`menu-item menu-transactions ${activeSection === 'transactions' ? 'active' : ''}`}
              onClick={() => setActiveSection('transactions')}
            >
              <span className="menu-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 5h6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                  <path d="M9 9h8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                  <path d="M9 13h8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                  <path d="M9 17h6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                  <path d="M7 3h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="currentColor" strokeWidth="2" />
                </svg>
              </span>
              <span className="menu-label">Transaction History</span>
            </div>

            <a className="menu-item menu-visitor" href="#/visitor" target="_blank" rel="noreferrer">
              <span className="menu-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" stroke="currentColor" strokeWidth="2" />
                  <path d="M6 12v7a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-7" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                  <path d="M9 9h6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
              </span>
              <span className="menu-label">Visitor Booking</span>
            </a>
          </>
        )}
        
        <div className="menu-item menu-logout" onClick={() => {
          try { localStorage.removeItem('adminRole'); } catch {}
          window.location.hash = '#/admin-login';
        }}>
          <span className="menu-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M10 17l1.5 1.5a2 2 0 0 0 1.4.6H20a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-7.1a2 2 0 0 0-1.4.6L10 7" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M15 12H3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              <path d="M6 9l-3 3 3 3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </span>
          <span className="menu-label">Logout</span>
        </div>
      </div>

      {/* Main Content */}
      <div className="main-content">
        <div className="content-header">
          {activeSection === 'dashboard' ? (
            <div className="dash-topbar">
              <div>
                <h1 className="dash-title">Dashboard Overview</h1>
                <p className="dash-subtitle">Welcome back, Admin. Here's today's meal report.</p>
              </div>

              <div className="dash-actions">
                <label className="dash-control" aria-label="Dashboard date">
                  <span className="dash-control-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M7 3v2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M17 3v2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M4 7h16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M6 5h12a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" stroke="currentColor" strokeWidth="2" />
                      <path d="M8 11h3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M13 11h3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M8 15h3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                    </svg>
                  </span>
                  <span className="dash-control-label">Today:</span>
                  <input
                    type="date"
                    value={dashboardDate}
                    onChange={(e) => setDashboardDate(e.target.value)}
                  />
                </label>

                <label className="dash-control" aria-label="Meal filter">
                  <span className="dash-control-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M7 3v8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M10 3v8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M7 7h3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M14 3v7a2 2 0 0 0 2 2h0V3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                      <path d="M5 21h14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      <path d="M12 21V11" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                    </svg>
                  </span>
                  <select value={dashboardMealFilter} onChange={(e) => setDashboardMealFilter(e.target.value)}>
                    <option value="">None</option>
                    {['Breakfast', 'Mid-Meal', 'Lunch', 'Dinner'].map((m) => (
                      <option key={m} value={m}>
                        {m}
                      </option>
                    ))}
                  </select>
                </label>

                <button type="button" className="btn btn-primary dash-export" onClick={exportDashboardPDF}>
                  Export to PDF
                </button>
              </div>
            </div>
          ) : (
            <h1>
              {activeSection === 'employees' ? 'Employee Management' : 
               activeSection === 'lookup' ? 'Employee Lookup' :
               activeSection === 'wallet' ? 'Wallet Recharge' :
               activeSection === 'scan' ? 'RFID Meal Scan' :
               activeSection === 'transactions' ? 'Transaction History' :
               'Dashboard'}
            </h1>
          )}
        </div>

        <div className="content-body">
          {/* Alert Messages */}
          {alert.show && (
            <div className={`alert alert-${alert.type}`}>
              {alert.message}
            </div>
          )}

          {/* Insights Dashboard */}
          {activeSection === 'dashboard' && (
            <>
              <div className="insight-grid">
                <div className="insight-card kpi kpi-employees">
                  <div className="kpi-top">
                    <div className="kpi-ico" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        <path d="M22 21v-2a3 3 0 0 0-2-2.82" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        <path d="M17 3.18a4 4 0 0 1 0 7.64" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      </svg>
                    </div>
                    <div className="kpi-pill">+0</div>
                  </div>
                  <h2>{totalEmployees.toLocaleString()}</h2>
                  <p className="kpi-label">Total Employees</p>
                  <span className="muted">Active profiles in system</span>
                </div>

                <div className="insight-card kpi kpi-wallet">
                  <div className="kpi-top">
                    <div className="kpi-ico" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" stroke="currentColor" strokeWidth="2" />
                        <path d="M3 9h18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        <path d="M16.5 15h1.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      </svg>
                    </div>
                    <div className="kpi-pill">+12%</div>
                  </div>
                  <h2>₹{totalWallet.toFixed(0).toLocaleString()}</h2>
                  <p className="kpi-label">Wallet Balance</p>
                  <span className="muted">Avg ₹{avgWallet.toFixed(0)} / employee</span>
                </div>

                <div className="insight-card kpi kpi-spend">
                  <div className="kpi-top">
                    <div className="kpi-ico" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 7h12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        <path d="M6 11h12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        <path d="M9 21l6-10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                        <path d="M6 17h8a4 4 0 0 0 0-8H6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                      </svg>
                    </div>
                    <div className="kpi-pill">0%</div>
                  </div>
                  <h2>₹{spendToday.toFixed(2)}</h2>
                  <p className="kpi-label">Today's Spend</p>
                  <span className="muted">
                    {dashboardMealFilter ? `${dashboardMealFilter} spend on ${dashboardIso}` : `Total spend on ${dashboardIso}`}
                  </span>
                </div>

                <div className="insight-card kpi kpi-participation">
                  <div className="kpi-top">
                    <div className="kpi-ico" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22c4 0 7-3 7-7 0-3-1.5-5.5-4-7.5.2 2.2-.6 3.7-2 5-1.6 1.4-2.8 2.7-2.8 4.8 0 2.7 1.9 4.7 4.8 4.7Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
                        <path d="M10 12c-.6 1-.9 1.9-.9 3 0 1.8 1.1 3 2.9 3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                      </svg>
                    </div>
                    <div className="kpi-pill">{participationPct}%</div>
                  </div>
                  <h2>
                    {participationCount} / {totalEmployees}
                  </h2>
                  <p className="kpi-label">Meal Participation</p>
                  <span className="muted">
                    {dashboardMealFilter 
                      ? `${dashboardMealFilter} visits on ${dashboardIso}` 
                      : `Unique visits on ${dashboardIso}`}
                  </span>
                </div>
              </div>

              <div className="chart-grid">
                <div className="chart-card">
                  <div className="chart-header">
                    <h3>Meal Distribution</h3>
                    <span className="muted">By count</span>
                  </div>
                  <div className="chart-body pie-wrap">
                    <div className="pie" style={pieStyle}>
                      <div className="pie-center">
                        <div className="pie-center-muted">Total</div>
                        <div className="pie-center-value">{mealTotal}</div>
                      </div>
                    </div>
                    <div className="legend">
                      {mealCounts.map((m, idx) => (
                        <div key={m.meal} className="legend-row">
                          <span className={`dot dot-${idx}`}></span>
                          <span>{m.meal}</span>
                          <span className="muted">{m.count}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                <div className="chart-card">
                  <div className="chart-header">
                    <div>
                      <h3>Spending Trends</h3>
                      <span className="muted">Spending vs Recharges</span>
                    </div>
                    <div className="trend-tabs" role="tablist" aria-label="Trend range">
                      <button
                        type="button"
                        className={`trend-tab ${trendRange === 'day' ? 'active' : ''}`}
                        onClick={() => setTrendRange('day')}
                      >
                        Day
                      </button>
                      <button
                        type="button"
                        className={`trend-tab ${trendRange === 'week' ? 'active' : ''}`}
                        onClick={() => setTrendRange('week')}
                      >
                        Week
                      </button>
                      <button
                        type="button"
                        className={`trend-tab ${trendRange === 'month' ? 'active' : ''}`}
                        onClick={() => setTrendRange('month')}
                      >
                        Month
                      </button>
                      <button
                        type="button"
                        className={`trend-tab ${trendRange === 'year' ? 'active' : ''}`}
                        onClick={() => setTrendRange('year')}
                      >
                        Year
                      </button>
                    </div>
                  </div>

                  <div className="trend-legend">
                    <div className="trend-legend-item">
                      <span className="trend-dot spend"></span>
                      <span>Spending</span>
                    </div>
                    <div className="trend-legend-item">
                      <span className="trend-dot recharge"></span>
                      <span>Recharges</span>
                    </div>
                  </div>

                  <div className="trend-chart">
                    <svg viewBox="0 0 560 200" preserveAspectRatio="none" aria-hidden="true">
                      {barGroups.map((g, i) => (
                        <g key={i}>
                          <rect x={g.xSpend} y={g.ySpend} width={g.width} height={g.hSpend} className="trend-bar spend" rx="2" />
                          <rect x={g.xRecharge} y={g.yRecharge} width={g.width} height={g.hRecharge} className="trend-bar recharge" rx="2" />
                        </g>
                      ))}
                    </svg>
                    <div className="trend-x">
                      {trendSeries.labels.map((l, i) => {
                        const show = trendRange === 'month' ? i % 5 === 0 : true;
                        return <span key={i} style={{ visibility: show ? 'visible' : 'hidden' }}>{l}</span>;
                      })}
                    </div>
                  </div>
                </div>
              </div>

            </>
          )}

          {/* Employee Lookup */}
          {activeSection === 'lookup' && (
            <div>
              <div className="lookup-page">
                <div className="card lookup-card">
                  <div className="lookup-header">
                    <div>
                      <h2>Find Employee</h2>
                      <p>Enter Employee ID, RFID, or Name to view employee details.</p>
                    </div>
                    <button
                      type="button"
                      className="btn btn-danger btn-small"
                      onClick={() => {
                        setLookupTerm('');
                        setLookupSelectedId(null);
                      }}
                    >
                      Clear
                    </button>
                  </div>

                  <div className="lookup-grid">
                    <div className="form-group">
                      <label>Search (Employee ID / RFID / Name)</label>
                      <input
                        type="text"
                        value={lookupTerm}
                        onChange={handleLookupChange}
                        placeholder="Type Employee ID, RFID number, or employee name"
                      />
                    </div>
                  </div>

                  <div className="lookup-meta">
                    {lookupTerm ? (
                      <span className="muted">Showing up to 20 matches. Matches: {lookupMatches.length}</span>
                    ) : (
                      <span className="muted">Start typing in any field to search.</span>
                    )}
                  </div>

                  {lookupMatches.length > 1 && (
                    <div className="lookup-results">
                      {lookupMatches.map((e) => (
                        <button
                          key={e.id}
                          type="button"
                          className={`lookup-row ${String(lookupSelectedId) === String(e.id) ? 'active' : ''}`}
                          onClick={() => setLookupSelectedId(e.id)}
                        >
                          <span className="lookup-name">{e.emp_name}</span>
                          <span className="lookup-sub muted">{e.emp_id} • {e.rfid_number}</span>
                        </button>
                      ))}
                    </div>
                  )}
                </div>

                <div className="card lookup-detail">
                  <h2>Employee Details</h2>

                  {lookupSelected ? (
                    <div className="lookup-detail-grid">
                      <div>
                        <span className="muted">Employee Name</span>
                        <div className="lookup-value">{lookupSelected.emp_name}</div>
                      </div>
                      <div>
                        <span className="muted">Employee ID</span>
                        <div className="lookup-value mono">{lookupSelected.emp_id}</div>
                      </div>
                      <div>
                        <span className="muted">RFID Number</span>
                        <div className="lookup-value mono">{lookupSelected.rfid_number}</div>
                      </div>
                      <div>
                        <span className="muted">Site</span>
                        <div className="lookup-value">{lookupSelected.site_name || '—'}</div>
                      </div>
                      <div>
                        <span className="muted">Shift</span>
                        <div className="lookup-value">{lookupSelected.shift || '—'}</div>
                      </div>
                      <div>
                        <span className="muted">Wallet Amount</span>
                        <div className="lookup-value">₹{parseFloat(lookupSelected.wallet_amount || 0).toFixed(2)}</div>
                      </div>
                    </div>
                  ) : (
                    <div className="muted" style={{ padding: '8px 0' }}>
                      {lookupTerm ? 'No employee selected. Choose a result above.' : 'Enter search details to view employee information.'}
                    </div>
                  )}
                </div>

                {lookupSelected && (
                  <div className="card lookup-history">
                    <h2>Transaction History</h2>
                    <div className="table-container">
                      <table>
                        <thead>
                          <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                          </tr>
                        </thead>
                        <tbody>
                          {lookupTransactions.length === 0 ? (
                            <tr>
                              <td colSpan="5" className="empty-cell">No transactions found for this employee.</td>
                            </tr>
                          ) : (
                            lookupTransactions.map((t) => (
                              <tr key={t.id}>
                                <td>{t.transaction_date}</td>
                                <td>{t.transaction_time}</td>
                                <td>
                                  <span style={{
                                    padding: '4px 8px',
                                    borderRadius: '4px',
                                    fontSize: '12px',
                                    background: t.transaction_type === 'deduction' ? '#fff3cd' : '#d4edda',
                                    color: '#0f172a'
                                  }}>
                                    {t.meal_category || (t.transaction_type === 'deduction' ? 'Meal' : 'Recharge')}
                                  </span>
                                </td>
                                <td style={{ 
                                  color: t.transaction_type === 'deduction' ? '#dc2626' : '#16a34a',
                                  fontWeight: 'bold'
                                }}>
                                  {t.transaction_type === 'deduction' ? '-' : '+'}₹{parseFloat(t.amount || 0).toFixed(2)}
                                </td>
                                <td>{t.new_balance ? `₹${parseFloat(t.new_balance).toFixed(2)}` : '—'}</td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* RFID Scan Section */}
          {activeSection === 'scan' && (
            <div className="rfid-section">
              <div className={`rfid-meal card ${currentMealInfo?.category ? 'meal-active' : 'meal-inactive'}`}>
                <div>
                  <p className="rfid-meal-label">Current Meal Slot</p>
                  <div className="rfid-meal-title">
                    {currentMealInfo?.category ? `${currentMealInfo.category} - ₹${currentMealInfo.amount}` : 'Outside Meal Hours'}
                  </div>
                  <p className="rfid-meal-time">
                    {currentMealInfo?.time_slot || 'No deductions at this time'}
                  </p>
                </div>
                <span className="meal-pill">{currentMealInfo?.category ? 'Active' : 'Inactive'}</span>
              </div>

              <div className="rfid-panels">
                <div className="rfid-card rfid-wait-card">
                  <div className="rfid-wave">
                    <div className="rfid-wave-inner">📶</div>
                  </div>
                  <h3 className="rfid-wait-title">Waiting for Scan...</h3>
                  <p className="rfid-wait-sub">Place the Employee RFID card near the reader to fetch details.</p>

                  <form className="rfid-form" onSubmit={handleRfidScan}>
                    <label className="rfid-form-label">Manual Entry (Employee ID / RFID)</label>
                    <div className="rfid-form-row">
                      <input
                        type="text"
                        id="rfidInput"
                        value={rfidInput}
                        onChange={(e) => setRfidInput(e.target.value)}
                        placeholder="Enter ID manually"
                        autoFocus
                        disabled={scanLoading}
                      />
                      <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={scanLoading}
                        style={{ minWidth: '96px' }}
                      >
                        {scanLoading ? 'Scanning...' : 'Scan'}
                      </button>
                    </div>
                  </form>
                </div>

                <div className="rfid-card rfid-result-card">
                  <div className={`rfid-status ${lastTransaction ? 'rfid-status-success' : 'rfid-status-idle'}`}>
                    <span>{lastTransaction ? 'Scan Successful' : 'Awaiting scan'}</span>
                    <span className="rfid-status-id">{lastTransaction?.rfid_number || 'RFID: —'}</span>
                  </div>

                  <h2 className="rfid-emp-name">{scannedEmployee?.name || scannedEmployee?.emp_name || '—'}</h2>
                  <p className="rfid-emp-id">{scannedEmployee?.emp_id || 'ID: —'}</p>

                  <div className="rfid-badges">
                    <div className="rfid-badge">
                      <span className="badge-label">Shift</span>
                      <span className="badge-value">{scannedEmployee?.shift || '—'}</span>
                    </div>
                    <div className="rfid-badge">
                      <span className="badge-label">Site</span>
                      <span className="badge-value">{scannedEmployee?.site || scannedEmployee?.site_name || '—'}</span>
                    </div>
                  </div>

                  <div className="rfid-balance-card">
                    <div>
                      <p className="balance-label">Current Wallet Balance</p>
                      <div className="balance-value">
                        ₹{scannedBalance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                      </div>
                      {lastTransaction && (
                        <p className="balance-note">Sufficient balance for meal</p>
                      )}
                    </div>
                  </div>

                  <div className="rfid-actions">
                    <button type="button" className="btn btn-danger" onClick={clearScanState}>
                      Cancel / Clear
                    </button>
                    <button
                      type="button"
                      className="btn btn-success"
                      onClick={() => handleRfidScan()}
                      disabled={scanLoading || !rfidInput.trim()}
                    >
                      Process Meal (-₹{currentMealInfo?.amount || '50'})
                    </button>
                  </div>

                  {lastTransaction && (
                    <div className="rfid-meta">
                      <div>
                        <span className="meta-label">Meal</span>
                        <span className="meta-value">{lastTransaction.meal_category}</span>
                      </div>
                      <div>
                        <span className="meta-label">Deducted</span>
                        <span className="meta-value">₹{lastTransaction.amount_deducted}</span>
                      </div>
                      <div>
                        <span className="meta-label">Date</span>
                        <span className="meta-value">{lastTransaction.transaction_date || lastTransaction.date}</span>
                      </div>
                      <div>
                        <span className="meta-label">Time</span>
                        <span className="meta-value">{lastTransaction.transaction_time || lastTransaction.time}</span>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Wallet Recharge Section */}
          {activeSection === 'wallet' && (
            <div className="wallet-section">
              <div className="wallet-header card">
                <div className="wallet-tabs">
                  <button
                    className={`wallet-tab ${walletMode === 'single' ? 'active' : ''}`}
                    onClick={() => setWalletMode('single')}
                  >
                    Single Recharge
                  </button>
                  <button
                    className={`wallet-tab ${walletMode === 'bulk' ? 'active' : ''}`}
                    onClick={() => setWalletMode('bulk')}
                  >
                    Bulk Recharge
                  </button>
                </div>
              </div>

              {walletMode === 'bulk' ? (
                <div className="card wallet-bulk">
                  <div className="bulk-icon">👥</div>
                  <h2>Mass Recharge</h2>
                  <p className="bulk-sub">The amount specified below will be added to every registered employee's wallet instantly.</p>

                  <div className="bulk-field">
                    <label htmlFor="bulkAmount">Amount to add to all (₹)</label>
                    <div className="input-row">
                      <input
                        type="number"
                        id="bulkAmount"
                        value={bulkRechargeAmount}
                        onChange={(e) => setBulkRechargeAmount(e.target.value)}
                        placeholder="₹ 0.00"
                        step="0.01"
                        min="0"
                      />
                    </div>
                    <div className="chip-row">
                      {[50, 2100, 2200, 2500].map((amt) => (
                        <button key={amt} type="button" className="chip" onClick={() => setBulkRechargeAmount(String(amt))}>
                          ₹{amt}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div className="bulk-warning">
                    <strong>⚠️</strong>
                    <span>This action will affect all employees in the system. Please double-check the amount before processing.</span>
                  </div>

                  <button className="btn btn-primary bulk-submit" onClick={bulkRechargeWallets}>
                    ⚡ Recharge All IDs
                  </button>
                </div>
              ) : (
                <>
                  <div className="wallet-grid">
                    <div className="card wallet-card">
                      {!searchedEmployee ? (
                        <>
                          <h3>Find Employee</h3>
                          <div className="search-field">
                            <label htmlFor="searchQuery">Scan RFID or ID</label>
                            <div className="input-row">
                              <input
                                type="text"
                                id="searchQuery"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Scan RFID or enter ID"
                                onKeyDown={(e) => e.key === 'Enter' && searchEmployee()}
                              />
                            </div>
                          </div>
                          <div className="wallet-actions-row">
                            <button className="btn btn-secondary" onClick={searchEmployee}>Scan RFID</button>
                            <button className="btn btn-primary" onClick={searchEmployee}>Search</button>
                          </div>
                        </>
                      ) : (
                        <>
                          <h3>Employee Found</h3>
                          <p className="muted" style={{ marginBottom: 12 }}>
                            {searchedEmployee.emp_name} • {searchedEmployee.emp_id}
                          </p>
                          <button
                            className="btn btn-primary"
                            style={{ width: '100%', marginBottom: 12 }}
                            onClick={() => setShowRechargeForm(true)}
                          >
                            Recharge
                          </button>

                          {showRechargeForm && (
                            <div className="recharge-panel">
                              <h3>Recharge Details</h3>
                              <div className="search-field">
                                <label htmlFor="rechargeAmount">Amount (₹)</label>
                                <div className="input-row">
                                  <input
                                    type="number"
                                    id="rechargeAmount"
                                    value={rechargeAmount}
                                    onChange={(e) => setRechargeAmount(e.target.value)}
                                    placeholder="₹ 0.00"
                                    step="0.01"
                                    min="0"
                                  />
                                </div>
                                <div className="chip-row">
                                  {[100, 500, 1000, 2000].map((amt) => (
                                    <button key={amt} type="button" className="chip" onClick={() => setRechargeAmount(String(amt))}>
                                      + ₹{amt}
                                    </button>
                                  ))}
                                </div>
                              </div>
                              <div className="wallet-actions-row">
                                <button className="btn btn-primary" onClick={rechargeIndividualWallet} disabled={!rechargeAmount}>
                                  ✓ Process Recharge
                                </button>
                                <button className="btn btn-danger" onClick={() => setRechargeAmount('')}>
                                  Reset
                                </button>
                              </div>
                            </div>
                          )}

                          <div className="wallet-actions-row" style={{ marginTop: 10 }}>
                            <button
                              className="btn btn-secondary"
                              onClick={() => {
                                setSearchedEmployee(null);
                                setRechargeAmount('');
                                setSearchQuery('');
                                setShowRechargeForm(false);
                              }}
                            >
                              Change Employee
                            </button>
                          </div>
                        </>
                      )}
                    </div>

                    <div className="card wallet-summary">
                      {searchedEmployee ? (
                        <div className="summary-content">
                          <div className="summary-header">
                            <div className="summary-avatar">
                              {getInitials(searchedEmployee.emp_name)}
                            </div>
                            <div className="summary-info">
                              <div className="summary-name">{searchedEmployee.emp_name}</div>
                              <div className="summary-sub">{searchedEmployee.emp_id}</div>
                            </div>
                            <span className="status-pill status-delivered">Active</span>
                          </div>

                          <div className="summary-balance-card">
                            <span className="balance-label">Wallet Balance</span>
                            <div className="summary-balance">
                              ₹{parseFloat(searchedEmployee.wallet_amount || 0).toFixed(2)}
                            </div>
                          </div>

                          <div className="summary-details-grid">
                            <div className="detail-item">
                              <span className="detail-label">Site Location</span>
                              <span className="detail-value">{searchedEmployee.site_name}</span>
                            </div>
                            <div className="detail-item">
                              <span className="detail-label">RFID Number</span>
                              <span className="detail-value mono">{searchedEmployee.rfid_number}</span>
                            </div>
                            <div className="detail-item">
                              <span className="detail-label">Shift</span>
                              <span className="detail-value">{searchedEmployee.shift || '—'}</span>
                            </div>
                          </div>
                        </div>
                      ) : (
                        <div className="summary-placeholder">
                           <div className="placeholder-icon">👤</div>
                           <h3>No Employee Selected</h3>
                           <p>Search for an employee on the left to view their details and process recharges.</p>
                        </div>
                      )}
                    </div>
                  </div>

                  {recentRecharges.length > 0 && (
                    <div className="card wallet-recent">
                      <div className="recent-header">
                        <div>
                          <h3>Recent Recharges</h3>
                          <p>Latest wallet top-ups.</p>
                        </div>
                      </div>
                      <div className="wallet-table">
                        <div className="wallet-row head">
                          <span>Txn ID</span>
                          <span>Employee</span>
                          <span>Amount</span>
                          <span>Method</span>
                          <span>Status</span>
                        </div>
                        {recentRecharges.map((t) => (
                          <div key={t.id} className="wallet-row">
                            <span>#{t.id}</span>
                            <span>
                              <strong>{t.emp_name}</strong>
                              <small style={{ display: 'block', color: '#6b7280' }}>{t.emp_id}</small>
                            </span>
                            <span className="recharge-amount">+ ₹{parseFloat(t.amount || 0).toFixed(2)}</span>
                            <span>{t.payment_method || '—'}</span>
                            <span><span className="status-pill status-delivered">{t.order_status || 'Success'}</span></span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </>
              )}
            </div>
          )}

          {/* Employee Management Section */}
          {activeSection === 'employees' && (
            <>
              {(() => {
                const query = employeeSearch.trim().toLowerCase();
                const filteredEmployees = !query
                  ? employees
                  : employees.filter((e) => {
                      const haystack = [
                        e.rfid_number,
                        e.emp_id,
                        e.emp_name,
                        e.site_name,
                        e.shift,
                      ]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase();
                      return haystack.includes(query);
                    });

                return (
                  <div className="employee-page">
                    {!isSecurity && (
                      <div className="card employee-card">
                        <h2 className="employee-title">Add New Employee</h2>
                        <form onSubmit={handleSubmit}>
                          <div className="employee-form-grid">
                            <div className="form-group">
                              <label htmlFor="rfid_number">RFID Number <span className="req">*</span></label>
                              <input
                                type="text"
                                id="rfid_number"
                                name="rfid_number"
                                value={formData.rfid_number}
                                onChange={handleInputChange}
                                placeholder="Scan or enter RFID"
                                required
                              />
                            </div>
                            <div className="form-group">
                              <label htmlFor="emp_id">Employee ID <span className="req">*</span></label>
                              <input
                                type="text"
                                id="emp_id"
                                name="emp_id"
                                value={formData.emp_id}
                                onChange={handleInputChange}
                                placeholder="e.g. EMP001"
                                required
                              />
                            </div>
                            <div className="form-group">
                              <label htmlFor="emp_name">Employee Name <span className="req">*</span></label>
                              <input
                                type="text"
                                id="emp_name"
                                name="emp_name"
                                value={formData.emp_name}
                                onChange={handleInputChange}
                                placeholder="Full Name"
                                required
                              />
                            </div>
                            <div className="form-group">
                              <label htmlFor="site_name">Site Name <span className="req">*</span></label>
                              <select
                                id="site_name"
                                name="site_name"
                                value={formData.site_name}
                                onChange={handleInputChange}
                                required
                              >
                                <option value="">Select Site</option>
                                <option value="Site A">Site A</option>
                                <option value="Site B">Site B</option>
                                <option value="Site C">Site C</option>
                                <option value="Site D">Site D</option>
                              </select>
                            </div>
                            <div className="form-group">
                              <label htmlFor="shift">Shift <span className="req">*</span></label>
                              <select
                                id="shift"
                                name="shift"
                                value={formData.shift}
                                onChange={handleInputChange}
                                required
                              >
                                <option value="">Select Shift</option>
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                                <option value="Night">Night</option>
                              </select>
                            </div>
                            <div className="form-group">
                              <label htmlFor="wallet_amount">Wallet Amount (₹) <span className="req">*</span></label>
                              <input
                                type="number"
                                id="wallet_amount"
                                name="wallet_amount"
                                value={formData.wallet_amount}
                                onChange={handleInputChange}
                                step="0.01"
                                min="0"
                                required
                              />
                            </div>
                          </div>
                          <div className="employee-actions">
                            <button type="submit" className="btn btn-primary btn-with-icon">
                              <span className="btn-ico">+</span>
                              Add Employee
                            </button>
                            <button type="button" className="btn btn-danger btn-with-icon" onClick={resetForm}>
                              <span className="btn-ico">×</span>
                              Cancel
                            </button>
                          </div>
                        </form>
                      </div>
                    )}

                    <div className="card employee-card">
                      <div className="employee-list-header">
                        <h2 className="employee-title">Employee List</h2>
                        <div className="employee-search">
                          <span className="search-ico" aria-hidden="true">🔍</span>
                          <input
                            value={employeeSearch}
                            onChange={(e) => setEmployeeSearch(e.target.value)}
                            placeholder="Search employees..."
                            aria-label="Search employees"
                          />
                        </div>
                      </div>

                      {loading ? (
                        <div className="loading" style={{ padding: '10px 0' }}>
                          <div className="spinner"></div>
                          <p>Loading...</p>
                        </div>
                      ) : (
                        <div className="employee-table-wrap">
                          <table className="employee-table">
                            <thead>
                              <tr>
                                <th>RFID Number</th>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Site</th>
                                <th>Shift</th>
                                <th>Wallet Amount</th>
                                <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              {filteredEmployees.length === 0 ? (
                                <tr>
                                  <td colSpan="7" className="empty-cell">No employees found</td>
                                </tr>
                              ) : (
                                filteredEmployees.map((employee) => (
                                  <tr key={employee.id}>
                                    <td className="mono">{employee.rfid_number}</td>
                                    <td className="mono">{employee.emp_id}</td>
                                    <td>
                                      <div className="emp-name-cell">
                                        <span className="emp-avatar">{getInitials(employee.emp_name)}</span>
                                        <span className="emp-name">{employee.emp_name}</span>
                                      </div>
                                    </td>
                                    <td>
                                      <span className="site-pill">{employee.site_name}</span>
                                    </td>
                                    <td>{employee.shift}</td>
                                    <td className="wallet-amt">₹{parseFloat(employee.wallet_amount || 0).toFixed(2)}</td>
                                    <td>
                                      {!isReadOnly && (
                                        <div className="employee-row-actions">
                                          <button className="btn btn-edit btn-small" onClick={() => editEmployee(employee.id)}>
                                            ✎ Edit
                                          </button>
                                          <button className="btn btn-delete btn-small" onClick={() => deleteEmployee(employee.id)}>
                                            🗑 Delete
                                          </button>
                                        </div>
                                      )}
                                    </td>
                                  </tr>
                                ))
                              )}
                            </tbody>
                          </table>
                        </div>
                      )}
                    </div>
                  </div>
                );
              })()}
            </>
          )}
          
          {/* Transaction History Section */}
          {activeSection === 'transactions' && (
            <>
              {/* Filters */}
              <div className="form-section">
                <h2>Filter Transactions</h2>
                <div className="form-grid" style={{ gridTemplateColumns: '1fr 1fr auto auto' }}>
                  <div className="form-group">
                    <label htmlFor="filterDate">Date</label>
                    <input
                      type="date"
                      id="filterDate"
                      value={transactionFilter.date}
                      onChange={(e) => setTransactionFilter({...transactionFilter, date: e.target.value})}
                    />
                  </div>
                  <div className="form-group">
                    <label htmlFor="filterMeal">Meal Category</label>
                    <select
                      id="filterMeal"
                      value={transactionFilter.mealCategory}
                      onChange={(e) => setTransactionFilter({...transactionFilter, mealCategory: e.target.value})}
                    >
                      <option value="">All Meals</option>
                      <option value="Breakfast">Breakfast</option>
                      <option value="Mid-Meal">Mid-Meal</option>
                      <option value="Lunch">Lunch</option>
                      <option value="Dinner">Dinner</option>
                    </select>
                  </div>
                  <div className="form-group" style={{ alignSelf: 'flex-end' }}>
                    <button 
                      className="btn btn-primary"
                      onClick={loadTransactions}
                      style={{ padding: '12px 30px' }}
                    >
                      Refresh
                    </button>
                  </div>
                  <div className="form-group" style={{ alignSelf: 'flex-end' }}>
                    <button
                      className="btn btn-secondary"
                      onClick={exportTransactionsPDF}
                      style={{ padding: '12px 24px' }}
                    >
                      Export PDF
                    </button>
                  </div>
                </div>
              </div>

              {/* Transactions Table */}
              {loading ? (
                <div className="loading">
                  <div className="spinner"></div>
                  <p>Loading transactions...</p>
                </div>
              ) : (
                <div className="table-container">
                  <h2>Transaction History ({transactions.length})</h2>
                  <table>
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Employee</th>
                        <th>RFID</th>
                        <th>Meal</th>
                        <th>Amount</th>
                        <th>Previous Balance</th>
                        <th>New Balance</th>
                        <th>Site</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {transactions.length === 0 ? (
                        <tr>
                          <td colSpan="10" style={{ textAlign: 'center' }}>
                            No transactions found
                          </td>
                        </tr>
                      ) : (
                        transactions.map((transaction) => (
                          <tr key={transaction.id}>
                            <td>{transaction.transaction_date}</td>
                            <td>{transaction.transaction_time}</td>
                            <td>
                              <div>
                                <strong>{transaction.emp_name || 'Visitor'}</strong>
                                <br />
                                <small style={{ color: '#666' }}>{transaction.emp_id || 'VIS'}</small>
                              </div>
                            </td>
                            <td>{transaction.rfid_number}</td>
                            <td>
                              <span style={{
                                padding: '4px 8px',
                                borderRadius: '4px',
                                background: transaction.meal_category === 'Breakfast' ? '#fff3cd' :
                                           transaction.meal_category === 'Lunch' ? '#d4edda' : '#d1ecf1',
                                color: '#000'
                              }}>
                                {transaction.meal_category || (transaction.transaction_type === 'visitor' ? 'Visitor Order' : 'Recharge')}
                              </span>
                            </td>
                            <td style={{ 
                              color: transaction.transaction_type === 'deduction' ? '#e74c3c' : '#27ae60',
                              fontWeight: 'bold'
                            }}>
                              {transaction.transaction_type === 'deduction' ? '-' : '+'}₹{parseFloat(transaction.amount || 0).toFixed(2)}
                            </td>
                            <td>{transaction.previous_balance ? `₹${parseFloat(transaction.previous_balance).toFixed(2)}` : '—'}</td>
                            <td style={{ fontWeight: 'bold', color: '#27ae60' }}>
                              {transaction.new_balance ? `₹${parseFloat(transaction.new_balance).toFixed(2)}` : '—'}
                            </td>
                            <td>{transaction.site_name || (transaction.transaction_type === 'visitor' ? 'Visitor' : '')}</td>
                            <td>
                              {(() => {
                                const status = (transaction.order_status || 'Pending').toLowerCase();
                                const label = status.charAt(0).toUpperCase() + status.slice(1);
                                const safeClass = status.replace(/\s+/g, '-');
                                return (
                                  <span className={`status-pill status-${safeClass}`}>
                                    {label}
                                  </span>
                                );
                              })()}
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              )}
            </>
          )}
        </div>
      </div>

      {/* Edit Modal */}
      {showEditModal && (
        <div className="modal">
          <div className="modal-content">
            <div className="modal-header">
              <h2>Edit Employee</h2>
              <span className="close-modal" onClick={() => setShowEditModal(false)}>
                &times;
              </span>
            </div>
            <form onSubmit={handleEditSubmit}>
              <div className="form-grid">
                <div className="form-group">
                  <label htmlFor="edit_rfid_number">RFID Number *</label>
                  <input
                    type="text"
                    id="edit_rfid_number"
                    name="rfid_number"
                    value={editData.rfid_number}
                    onChange={handleEditInputChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="edit_emp_id">Employee ID *</label>
                  <input
                    type="text"
                    id="edit_emp_id"
                    name="emp_id"
                    value={editData.emp_id}
                    onChange={handleEditInputChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="edit_emp_name">Employee Name *</label>
                  <input
                    type="text"
                    id="edit_emp_name"
                    name="emp_name"
                    value={editData.emp_name}
                    onChange={handleEditInputChange}
                    required
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="edit_site_name">Site Name *</label>
                  <select
                    id="edit_site_name"
                    name="site_name"
                    value={editData.site_name}
                    onChange={handleEditInputChange}
                    required
                  >
                    <option value="">Select Site</option>
                    <option value="Site A">Site A</option>
                    <option value="Site B">Site B</option>
                    <option value="Site C">Site C</option>
                    <option value="Site D">Site D</option>
                  </select>
                </div>
                <div className="form-group">
                  <label htmlFor="edit_shift">Shift *</label>
                  <select
                    id="edit_shift"
                    name="shift"
                    value={editData.shift}
                    onChange={handleEditInputChange}
                    required
                  >
                    <option value="">Select Shift</option>
                    <option value="Morning">Morning (6 AM - 2 PM)</option>
                    <option value="Evening">Evening (2 PM - 10 PM)</option>
                    <option value="Night">Night (10 PM - 6 AM)</option>
                  </select>
                </div>
                <div className="form-group">
                  <label htmlFor="edit_wallet_amount">Wallet Amount (₹) *</label>
                  <input
                    type="number"
                    id="edit_wallet_amount"
                    name="wallet_amount"
                    value={editData.wallet_amount}
                    onChange={handleEditInputChange}
                    step="0.01"
                    min="0"
                    required
                  />
                </div>
              </div>
              <button type="submit" className="btn btn-success">
                Update Employee
              </button>
              <button
                type="button"
                className="btn btn-danger"
                onClick={() => setShowEditModal(false)}
                style={{ marginLeft: '10px' }}
              >
                Cancel
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default AdminDashboard;
