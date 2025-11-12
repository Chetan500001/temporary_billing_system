<?php
session_start();

if (!isset($_SESSION['customer_id']) || !isset($_GET['business_id'])) {
    header("Location: find_businesses.php");
    exit();
}

$business_id = $_GET['business_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "billing_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get business name
$bizStmt = $conn->prepare("SELECT business_name FROM businesses WHERE id = ?");
$bizStmt->bind_param("i", $business_id);
$bizStmt->execute();
$business = $bizStmt->get_result()->fetch_assoc();
$bizStmt->close();

// Get cart items
$cartStmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.product_name, p.offered_price 
    FROM cart c
    JOIN product p ON c.product_id = p.id
    WHERE c.customer_id = ? AND c.business_id = ?
");
$cartStmt->bind_param("ii", $_SESSION['customer_id'], $business_id);
$cartStmt->execute();
$cartItems = $cartStmt->get_result();

// Calculate total
$total = 0;
$cartData = [];
while ($item = $cartItems->fetch_assoc()) {
    $total += $item['offered_price'] * $item['quantity'];
    $cartData[] = $item;
}
$cartItems->data_seek(0); // Reset pointer
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
        .header { background: linear-gradient(90deg, #205781, #4F959D); color: white; padding: 15px 0; position: relative; }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 15px; position: relative; }
        .back-btn { position: absolute; top: 50%; left: 15px; transform: translateY(-50%); background: white; color: #205781; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .container { max-width: 800px; margin: 20px auto; padding: 0 15px; }
        .cart-title { text-align: center; font-size: 24px; margin-bottom: 20px; }
        .cart-item { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .item-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .item-name { font-weight: 600; }
        .item-price { color: #4F959D; font-weight: 600; }
        .item-quantity { display: flex; align-items: center; gap: 10px; margin: 10px 0; }
        .quantity-btn { width: 30px; height: 30px; border-radius: 50%; border: 1px solid #ddd; background: white; cursor: pointer; }
        .item-total { text-align: right; font-weight: 600; }
        .cart-total { background: white; border-radius: 8px; padding: 15px; margin-top: 20px; text-align: right; font-size: 18px; font-weight: 700; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .cart-actions { display: flex; justify-content: space-between; margin-top: 20px; gap: 10px; flex-wrap: wrap; }
        .action-btn { padding: 10px 20px; border-radius: 5px; border: none; cursor: pointer; font-weight: 600; transition: all 0.2s; }
        .continue-btn { background: #f0f0f0; color: #333; }
        .checkout-btn { background: #205781; color: white; }
        .clear-btn { background: #d9534f; color: white; }
        .save-btn { background: #5cb85c; color: white; }
        .history-btn { background: #6f42c1; color: white; }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        
        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 80%; max-width: 700px; }
        .close-modal { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-modal:hover { color: black; }
        .saved-bill { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .saved-bill h4 { margin-top: 0; color: #205781; }
        .bill-item { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .bill-date { color: #666; font-size: 14px; }
        .bill-total { font-weight: bold; text-align: right; margin-top: 10px; }
        
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .container { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="view_business_products.php?business_id=<?php echo $business_id; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <h2 style="text-align: center; margin: 0;">Your Cart</h2>
        </div>
    </div>

    <div class="container">
        <h1 class="cart-title"><?php echo htmlspecialchars($business['business_name']); ?></h1>
        
        <?php if (count($cartData) == 0): ?>
            <div style="text-align: center; padding: 40px 0;">
                <p style="font-size: 18px;">Your cart is empty</p>
                <a href="view_business_products.php?business_id=<?php echo $business_id; ?>" class="action-btn continue-btn">Browse Products</a>
            </div>
        <?php else: ?>
            <?php foreach ($cartData as $item): ?>
                <div class="cart-item">
                    <div class="item-header">
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-price">₹<?php echo $item['offered_price']; ?></div>
                    </div>
                    <div class="item-quantity">
                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">-</button>
                        <span><?php echo $item['quantity']; ?></span>
                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">+</button>
                    </div>
                    <div class="item-total">₹<?php echo $item['offered_price'] * $item['quantity']; ?></div>
                </div>
            <?php endforeach; ?>
            
            <div class="cart-total">
                Total: ₹<?php echo $total; ?>
            </div>
            
            <div class="cart-actions no-print">
                <a href="view_business_products.php?business_id=<?php echo $business_id; ?>" class="action-btn continue-btn">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                <button class="action-btn clear-btn" onclick="clearCart()">
                    <i class="fas fa-trash-alt"></i> Clear Cart
                </button>
                <button class="action-btn save-btn" onclick="saveBill()">
                    <i class="fas fa-save"></i> Save Bill
                </button>
                <button class="action-btn history-btn" onclick="showSavedBills()">
                    <i class="fas fa-history"></i> View Saved Bills
                </button>
                <button class="action-btn checkout-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Bill
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Saved Bills Modal -->
    <div id="savedBillsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>Your Saved Bills</h2>
            <div id="saved-bills-container">
                <!-- Bills will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Update product quantity in cart
function updateQuantity(cartId, change) {
    const quantityBtn = event.target;
    quantityBtn.disabled = true;
    
    $.ajax({
        url: 'update_cart_quantity.php',
        method: 'POST',
        data: {
            cart_id: cartId,
            change: change
        },
        success: function() {
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Quantity update error:', error);
            alert('Error updating quantity. Please try again.');
            quantityBtn.disabled = false;
        }
    });
}

function clearCart() {
    if(!confirm('Are you sure you want to clear your cart?')) return;
    
    const clearBtn = document.querySelector('.clear-btn');
    const originalText = clearBtn.innerHTML;
    clearBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
    clearBtn.disabled = true;
    
    $.ajax({
        url: 'clear_cart.php',
        method: 'POST',
        dataType: 'json',
        data: {
            business_id: <?php echo $business_id; ?>
        },
        success: function(response) {
            // Always refresh on success, even if response parsing fails
            location.reload();
        },
        error: function(xhr) {
            // Show error but still refresh
            alert('Cart cleared, but may not have updated automatically. Refreshing...');
            location.reload();
        },
        complete: function() {
            clearBtn.innerHTML = originalText;
            clearBtn.disabled = false;
        }
    });
}
// Save current cart as a bill
function saveBill() {
    const billName = prompt("Enter a name for this bill:", "Bill_<?php echo date('Ymd_His'); ?>");
    if (billName === null || billName.trim() === "") {
        alert("Please enter a valid bill name");
        return;
    }
    
    const saveBtn = document.querySelector('.save-btn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
    const cartData = <?php echo json_encode($cartData); ?>;
    const total = <?php echo $total; ?>;
    const businessId = <?php echo $business_id; ?>;
    const businessName = "<?php echo addslashes($business['business_name']); ?>";
    
    $.ajax({
        url: 'save_bill.php',
        method: 'POST',
        dataType: 'json',
        data: {
            customer_id: <?php echo $_SESSION['customer_id']; ?>,
            business_id: businessId,
            business_name: businessName,
            bill_name: billName,
            items: JSON.stringify(cartData),
            total: total
        },
        success: function(response) {
            if (response && response.success) {
                alert('Bill saved successfully! Bill ID: ' + response.bill_id);
            } else {
                const errorMsg = response && response.message 
                    ? response.message 
                    : 'Bill may have saved but received unexpected response';
                alert(errorMsg);
            }
        },
        error: function(xhr) {
            let errorMsg = 'Failed to save bill';
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                errorMsg = jsonResponse.message || errorMsg;
            } catch (e) {
                console.error('Error parsing response:', e);
                errorMsg = 'Server error occurred. Please check console.';
            }
            alert(errorMsg);
        },
        complete: function() {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    });
}

// Show saved bills modal
function showSavedBills() {
    const modal = document.getElementById('savedBillsModal');
    modal.style.display = "block";
    
    // Show loading state
    const container = $('#saved-bills-container');
    container.html('<div class="loading-text"><i class="fas fa-spinner fa-spin"></i> Loading bills...</div>');
    
    $.ajax({
        url: 'get_saved_bills.php',
        method: 'GET',
        data: {
            customer_id: <?php echo $_SESSION['customer_id']; ?>,
            business_id: <?php echo $business_id; ?>
        },
        success: function(response) {
            container.html(response);
        },
        error: function(xhr) {
            let errorMsg = 'Failed to load saved bills';
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                errorMsg = jsonResponse.message || errorMsg;
            } catch (e) {
                console.error('Error parsing response:', e);
            }
            container.html('<p class="error-message">' + errorMsg + '</p>');
        }
    });
}

// Delete a saved bill
function deleteSavedBill(billId) {
    if(!confirm('Are you sure you want to permanently delete this saved bill?')) return;
    
    const deleteBtn = event.target;
    const originalText = deleteBtn.innerHTML;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    deleteBtn.disabled = true;
    
    $.ajax({
        url: 'delete_saved_bill.php',
        method: 'POST',
        dataType: 'json',
        data: { bill_id: billId },
        success: function(response) {
            if(response && response.success) {
                // Close modal and refresh page
                $('#savedBillsModal').hide();
                location.reload();
            } else {
                const errorMsg = response && response.message 
                    ? response.message 
                    : 'Bill may have deleted but received unexpected response';
                alert(errorMsg);
            }
        },
        error: function(xhr) {
            let errorMsg = 'Failed to delete bill';
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                errorMsg = jsonResponse.message || errorMsg;
            } catch (e) {
                console.error('Error parsing response:', e);
                errorMsg = 'Server error occurred. Please check console.';
            }
            alert(errorMsg);
        },
        complete: function() {
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        }
    });
}

// Close modal
function closeModal() {
    document.getElementById('savedBillsModal').style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('savedBillsModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Print bill
function printBill() {
    window.print();
}
</script>
</body>
</html>
<?php
$cartStmt->close();
$conn->close();
?>