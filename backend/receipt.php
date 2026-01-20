<?php
/**
 * Receipt Display Page
 * Shows transaction details when QR is scanned
 */

date_default_timezone_set('Asia/Kolkata');

// Determine if we're in root or backend folder
$configPath = file_exists(__DIR__ . '/config/database.php') 
    ? __DIR__ . '/config/database.php' 
    : __DIR__ . '/backend/config/database.php';

require_once $configPath;

$database = new Database();
$db = $database->getConnection();

$transactionId = $_GET['id'] ?? null;

// Try multiple logo paths
$logoPath = null;
$possiblePaths = [
    __DIR__ . '/logo.png',
    __DIR__ . '/../logo.png',
    __DIR__ . '/../Catalyst_Logo_Final_12-01-2023.jpg',
    __DIR__ . '/Catalyst_Logo_Final_12-01-2023.jpg',
    __DIR__ . '/../backend/Catalyst_Logo_Final_12-01-2023.jpg'
];

foreach ($possiblePaths as $path) {
    $resolved = realpath($path);
    if ($resolved && file_exists($resolved)) {
        $logoPath = $resolved;
        break;
    }
}

$logoBase64 = ($logoPath && file_exists($logoPath)) ? base64_encode(file_get_contents($logoPath)) : null;

if (!$transactionId) {
    http_response_code(400);
    echo "Transaction ID not provided";
    exit;
}

try {
    $query = "SELECT t.*, e.emp_name, e.emp_id, e.site_name, e.rfid_number
              FROM transactions t
              LEFT JOIN employees e ON t.employee_id = e.id
              WHERE t.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $transactionId);
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        http_response_code(404);
        echo "Transaction not found";
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Receipt - CATALYST</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* wrapper guarantees true horizontal centering across browsers/print */
        .receipt-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        
        .receipt-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            margin: 0 auto;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .receipt-header h1 {
            color: #667eea;
            font-size: 30px;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }
        
        .receipt-header p {
            color: #666;
            font-size: 11px;
            letter-spacing: 0.4px;
        }
        
        .divider {
            border-top: 2px dashed #ddd;
            margin: 20px 0;
        }
        
        .meal-badge {
            background: #667eea;
            color: white;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .receipt-info {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            color: #555;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
        }
        
        .amount-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            margin: 20px 0;
        }
        
        .amount-label {
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .amount-value {
            font-size: 32px;
            color: #e74c3c;
            font-weight: bold;
        }
        
        .balance-section {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        
        .balance-label {
            color: #2e7d32;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .balance-value {
            font-size: 28px;
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-badge {
            display: inline-block;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 20px;
            text-transform: capitalize;
        }

        .status-badge.pending { background: #f59e0b; }
        .status-badge.delivered { background: #16a34a; }
        .status-badge.cancelled { background: #ef4444; }

        .status-actions {
            margin-top: 12px;
            text-align: center;
        }

        .status-action-btn {
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .status-action-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .status-action-btn:hover:not(:disabled) {
            background: #1666cb;
        }

        .status-message {
            margin-top: 8px;
            font-size: 12px;
            color: #374151;
        }
        
        .footer {
            text-align: center;
            color: #999;
            font-size: 11px;
            margin-top: 20px;
        }

        .logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo img {
            max-height: 120px;
            width: auto;
            display: inline-block;
        }

        @media print {
            body {
                background: white;
                min-height: auto;
                display: block;
                padding: 0;
                margin: 0;
            }
            .receipt-wrapper {
                width: 100%;
                justify-content: center;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 360px;
                width: 100%;
                padding: 24px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-wrapper">
        <div class="receipt-container">
        <div class="receipt-header">
            <div class="logo">
                <?php if ($logoBase64): ?>
                    <img src="data:image/jpeg;base64,<?php echo $logoBase64; ?>" alt="CATALYST" />
                <?php else: ?>
                    <h1>CATALYST</h1>
                <?php endif; ?>
            </div>
            <p>Partnering for Sustainability</p>
        </div>
        
        <div class="divider"></div>
        
        <div class="meal-badge">
            <?php echo htmlspecialchars($transaction['meal_category']); ?>
        </div>
        
        <div class="receipt-info">
            <div class="info-row">
                <span class="info-label">Employee</span>
                <span class="info-value"><?php echo htmlspecialchars($transaction['emp_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Employee ID</span>
                <span class="info-value"><?php echo htmlspecialchars($transaction['emp_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">RFID</span>
                <span class="info-value"><?php echo htmlspecialchars($transaction['rfid_number']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Site</span>
                <span class="info-value"><?php echo htmlspecialchars($transaction['site_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date</span>
                <span class="info-value"><?php echo htmlspecialchars($transaction['transaction_date']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Time</span>
                <span class="info-value"><?php echo htmlspecialchars($transaction['transaction_time']); ?></span>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="amount-section">
            <div class="amount-label">Amount Deducted</div>
            <div class="amount-value">Rs. <?php echo number_format($transaction['amount'], 2); ?></div>
        </div>
        
        <div class="balance-section">
            <div class="balance-label">Available Balance</div>
            <div class="balance-value">Rs. <?php echo number_format($transaction['new_balance'], 2); ?></div>
        </div>
        
        <div style="text-align: center;">
            <?php 
                $orderStatus = strtolower($transaction['order_status'] ?? 'Pending');
                $orderStatusLabel = ucfirst($orderStatus);
            ?>
            <span id="orderStatus" class="status-badge <?php echo $orderStatus; ?>">
                <?php echo htmlspecialchars($orderStatusLabel); ?>
            </span>
            <div class="status-actions">
                <button 
                    id="markDeliveredBtn" 
                    class="status-action-btn"
                    <?php echo $orderStatus === 'delivered' ? 'disabled' : ''; ?>
                >
                    <?php echo $orderStatus === 'delivered' ? 'Delivered' : 'Mark as Delivered'; ?>
                </button>
                <div id="statusMessage" class="status-message"></div>
            </div>
        </div>
        
        <div class="footer">
            <p>Transaction ID: <?php echo htmlspecialchars($transaction['id']); ?></p>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        </div>
    </div>

    <script>
        (function() {
            const button = document.getElementById('markDeliveredBtn');
            const statusEl = document.getElementById('orderStatus');
            const messageEl = document.getElementById('statusMessage');
            if (!button || !statusEl) return;

            const transactionId = <?php echo json_encode($transactionId); ?>;
            const path = window.location.pathname;
            const apiBase = path.includes('/qsr/') ? '/qsr/Tap-N-Eat/api' : '/Tap-N-Eat/api';
            const apiUrl = `${window.location.origin}${apiBase}/transactions.php`;

            const setStatus = (status) => {
                const safeStatus = (status || 'Pending').trim();
                const normalized = safeStatus.toLowerCase();
                statusEl.textContent = safeStatus;
                statusEl.className = `status-badge ${normalized}`;
            };

            button.addEventListener('click', async () => {
                if (button.disabled) return;
                messageEl.textContent = 'Updating status...';
                button.disabled = true;

                try {
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ transaction_id: transactionId, status: 'Delivered' })
                    });

                    const result = await response.json();

                    if (!response.ok || result.status !== 'success') {
                        throw new Error(result.message || 'Failed to update status');
                    }

                    setStatus('Delivered');
                    button.textContent = 'Delivered';
                    messageEl.textContent = 'Status updated successfully.';
                } catch (error) {
                    messageEl.textContent = error.message;
                    button.disabled = false;
                }
            });

            setStatus(statusEl.textContent);
        })();
    </script>
</body>
</html>
?>
