<?php
session_start();
header('Content-Type: text/html');

// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "billing_system";

try {
    // Create database connection
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");

    // Validate session and input
    if (!isset($_SESSION['customer_id']) || !isset($_GET['business_id'])) {
        throw new Exception("Session expired or invalid request");
    }

    $customer_id = filter_var($_SESSION['customer_id'], FILTER_VALIDATE_INT);
    $business_id = filter_var($_GET['business_id'], FILTER_VALIDATE_INT);

    if (!$customer_id || !$business_id) {
        throw new Exception("Invalid parameters received");
    }

    // Get saved bills from database
    $stmt = $conn->prepare("
        SELECT id, business_name, bill_name, items, total, date 
        FROM saved_bills 
        WHERE customer_id = ? AND business_id = ?
        ORDER BY date DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Database query preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $customer_id, $business_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<div class="no-bills">No saved bills found for this business.</div>';
        exit;
    }

    // Display bills
    while ($bill = $result->fetch_assoc()):
        $items = json_decode($bill['items'], true);
        ?>
        <div class="saved-bill">
            <div class="bill-header">
                <h4><?php echo htmlspecialchars($bill['bill_name']); ?></h4>
                <span class="bill-date"><?php echo date('d M Y, h:i A', strtotime($bill['date'])); ?></span>
            </div>
            
            <div class="bill-items">
                <?php foreach ($items as $item): ?>
                    <div class="bill-item">
                        <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                        <span class="item-quantity"><?php echo $item['quantity']; ?> ×</span>
                        <span class="item-price">₹<?php echo number_format($item['offered_price'], 2); ?></span>
                        <span class="item-total">₹<?php echo number_format($item['offered_price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="bill-footer">
                <div class="bill-total">
                    Total: ₹<?php echo number_format($bill['total'], 2); ?>
                </div>
                
                <div class="bill-actions">
                    <button onclick="printBill(<?php echo $bill['id']; ?>)" class="action-btn print-btn">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="deleteSavedBill(<?php echo $bill['id']; ?>)" class="action-btn delete-btn">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    <?php endwhile;
    
} catch (Exception $e) {
    echo '<div class="error-message">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>

<style>
.saved-bill {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.saved-bill:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.bill-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.bill-date {
    color: #666;
    font-size: 14px;
}
.bill-items {
    margin-bottom: 15px;
}
.bill-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px dashed #eee;
    align-items: center;
}
.bill-item:last-child {
    border-bottom: none;
}
.item-total {
    font-weight: 600;
    text-align: right;
}
.bill-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}
.bill-total {
    font-weight: 700;
    font-size: 16px;
}
.bill-actions {
    display: flex;
    gap: 10px;
}
.action-btn {
    padding: 8px 15px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}
.print-btn {
    background: #205781;
    color: white;
}
.delete-btn {
    background: #d9534f;
    color: white;
}
.action-btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}
.error-message {
    color: #d9534f;
    padding: 15px;
    text-align: center;
    background: #f8d7da;
    border-radius: 5px;
    margin: 20px 0;
    border: 1px solid #f5c6cb;
}
.no-bills {
    text-align: center;
    padding: 30px;
    color: #666;
    background: #f8f9fa;
    border-radius: 8px;
}
@media (max-width: 600px) {
    .bill-item {
        grid-template-columns: 1fr 1fr;
    }
    .item-price {
        text-align: right;
    }
    .bill-footer {
        flex-direction: column;
        gap: 15px;
    }
    .bill-actions {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

<script>
function printBill(billId) {
    // Open print view in new window
    window.open('print_bill.php?bill_id=' + billId, '_blank');
}

function deleteSavedBill(billId) {
    if (confirm('Are you sure you want to delete this saved bill?')) {
        fetch('delete_saved_bill.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'bill_id=' + billId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the bills list
                document.querySelector('.saved-bills-container').innerHTML = 
                    '<div class="loading">Loading...</div>';
                // Reload bills after deletion
                loadSavedBills();
            } else {
                alert('Error deleting bill: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete bill');
        });
    }
}
</script>