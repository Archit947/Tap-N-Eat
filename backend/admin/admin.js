// API Base URL - Update this with your server URL
const API_BASE_URL = 'http://localhost/QSR_New/Myqsr/backend/api';

let currentEditId = null;

// Load employees when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadEmployees();
});

// Add Employee Form Submit
document.getElementById('employeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        rfid_number: document.getElementById('rfidNumber').value,
        emp_id: document.getElementById('empId').value,
        emp_name: document.getElementById('empName').value,
        site_name: document.getElementById('siteName').value,
        shift: document.getElementById('shift').value
    };

    if (currentEditId) {
        // Update existing employee
        formData.id = currentEditId;
        await updateEmployee(formData);
    } else {
        // Create new employee
        await createEmployee(formData);
    }
});

// Edit Employee Form Submit
document.getElementById('editEmployeeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        id: document.getElementById('editEmployeeId').value,
        rfid_number: document.getElementById('editRfidNumber').value,
        emp_id: document.getElementById('editEmpId').value,
        emp_name: document.getElementById('editEmpName').value,
        site_name: document.getElementById('editSiteName').value,
        shift: document.getElementById('editShift').value
    };

    await updateEmployee(formData);
});

// Load all employees
async function loadEmployees() {
    try {
        showLoading(true);
        const response = await fetch(`${API_BASE_URL}/employees.php`);
        const employees = await response.json();
        
        displayEmployees(employees);
        showLoading(false);
    } catch (error) {
        console.error('Error loading employees:', error);
        showAlert('Error loading employees', 'error');
        showLoading(false);
    }
}

// Display employees in table
function displayEmployees(employees) {
    const tbody = document.getElementById('employeesTableBody');
    tbody.innerHTML = '';

    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No employees found</td></tr>';
        return;
    }

    employees.forEach(employee => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${employee.rfid_number}</td>
            <td>${employee.emp_id}</td>
            <td>${employee.emp_name}</td>
            <td>${employee.site_name}</td>
            <td>${employee.shift}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-edit" onclick="editEmployee(${employee.id})">Edit</button>
                    <button class="btn btn-danger" onclick="deleteEmployee(${employee.id})" style="padding: 8px 16px; font-size: 14px;">Delete</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Create new employee
async function createEmployee(data) {
    try {
        const response = await fetch(`${API_BASE_URL}/employees.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
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
}

// Update employee
async function updateEmployee(data) {
    try {
        const response = await fetch(`${API_BASE_URL}/employees.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Employee updated successfully!', 'success');
            closeEditModal();
            resetForm();
            loadEmployees();
        } else {
            showAlert(result.message || 'Error updating employee', 'error');
        }
    } catch (error) {
        console.error('Error updating employee:', error);
        showAlert('Error updating employee', 'error');
    }
}

// Edit employee - Load data into modal
async function editEmployee(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/employees.php?id=${id}`);
        const employee = await response.json();

        if (response.ok) {
            document.getElementById('editEmployeeId').value = employee.id;
            document.getElementById('editRfidNumber').value = employee.rfid_number;
            document.getElementById('editEmpId').value = employee.emp_id;
            document.getElementById('editEmpName').value = employee.emp_name;
            document.getElementById('editSiteName').value = employee.site_name;
            document.getElementById('editShift').value = employee.shift;

            document.getElementById('editModal').classList.add('show');
        } else {
            showAlert('Error loading employee data', 'error');
        }
    } catch (error) {
        console.error('Error loading employee:', error);
        showAlert('Error loading employee data', 'error');
    }
}

// Delete employee
async function deleteEmployee(id) {
    if (!confirm('Are you sure you want to delete this employee?')) {
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
}

// Reset form
function resetForm() {
    document.getElementById('employeeForm').reset();
    currentEditId = null;
    document.getElementById('submitBtnText').textContent = 'Add Employee';
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
    document.getElementById('editEmployeeForm').reset();
}

// Show alert message
function showAlert(message, type) {
    const alertElement = document.getElementById('alertMessage');
    alertElement.textContent = message;
    alertElement.className = `alert ${type === 'success' ? 'alert-success' : 'alert-error'} show`;

    setTimeout(() => {
        alertElement.classList.remove('show');
    }, 5000);
}

// Show/hide loading spinner
function showLoading(show) {
    const loadingElement = document.getElementById('loading');
    loadingElement.style.display = show ? 'block' : 'none';
}

// Navigation functions
function showSection(section) {
    // Update active menu item
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    event.target.classList.add('active');

    // In a real application, you would load different content based on the section
    if (section === 'employees') {
        loadEmployees();
    } else {
        showAlert(`${section.charAt(0).toUpperCase() + section.slice(1)} section - Coming soon!`, 'success');
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Implement logout logic here
        window.location.href = 'login.html';
    }
}
