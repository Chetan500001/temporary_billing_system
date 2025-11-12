<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['state'] = $_POST['state'];
    $_SESSION['city'] = $_POST['city'];
}
$conn = new mysqli('localhost', 'root', '', 'billing_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Popular products
$popular_products = [];
$stmt = $conn->prepare("SELECT p.*, b.business_name 
                        FROM product p 
                        JOIN businesses b ON p.business_id = b.id 
                        WHERE b.city = ? 
                        LIMIT 5");
$stmt->bind_param("s", $_SESSION['city']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $popular_products[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product Search</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      background-color: #F9F9F9;
    }

    .header {
      background-color: #205781;
      color: #fff;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 18px;
    }

    .header a {
      color: #fff;
      text-decoration: none;
      font-weight: 600;
    }

    .container {
      padding: 40px 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .search-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
      margin-bottom: 30px;
    }

    .search-bar input, .search-bar select {
      padding: 12px 15px;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 10px;
      min-width: 200px;
    }

    .search-bar .btn {
      background-color: #4F959D;
      color: #fff;
      border: none;
      padding: 12px 25px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .search-bar .btn:hover {
      background-color: #3c7b85;
    }

    .section-title {
      font-size: 22px;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      text-align: center;
    }

    .product-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
    }

    .product-card {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: transform 0.3s;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .product-card:hover {
      transform: translateY(-5px);
    }

    .product-name {
      font-size: 18px;
      font-weight: bold;
      color: #205781;
      margin-bottom: 5px;
    }

    .product-seller {
      font-size: 14px;
      color: #888;
      margin-bottom: 10px;
    }

    .product-price {
      font-size: 18px;
      font-weight: bold;
      color: #4F959D;
    }

    .original-price {
      text-decoration: line-through;
      color: #aaa;
      font-size: 14px;
      margin-right: 10px;
    }

    .rating {
      margin-top: 15px;
    }

    .rating select {
      padding: 5px;
      font-size: 14px;
      border-radius: 5px;
    }

    .no-results {
      text-align: center;
      padding: 40px;
      font-size: 16px;
      color: #999;
    }

    @media (max-width: 600px) {
      .search-bar {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>
<body>

  <div class="header">
    <div><?php echo $_SESSION['customer_name']; ?> — <?php echo $_SESSION['city']; ?>, <?php echo $_SESSION['state']; ?></div>
    <a href="logout.php">Logout</a>
  </div>

  <div class="container">

    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="🔍 Search for products..." />
      <select id="filterSelect">
        <option value="all">All Businesses</option>
        <?php
        $stmt = $conn->prepare("SELECT DISTINCT id, business_name FROM businesses WHERE city = ?");
        $stmt->bind_param("s", $_SESSION['city']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          echo '<option value="'.$row['id'].'">'.$row['business_name'].'</option>';
        }
        $stmt->close();
        ?>
      </select>

      <select id="categorySelect">
        <option value="all">All Categories</option>
        <?php
        $stmt = $conn->prepare("SELECT DISTINCT category FROM product WHERE business_id IN (SELECT id FROM businesses WHERE city = ?)");
        $stmt->bind_param("s", $_SESSION['city']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          echo '<option value="'.$row['category'].'">'.$row['category'].'</option>';
        }
        $stmt->close();
        ?>
      </select>

      <button class="btn" onclick="searchProducts()">Search</button>
    </div>

    <div class="section-title">Popular Products in <?php echo $_SESSION['city']; ?></div>
    <div class="product-list" id="popularProducts">
      <?php foreach ($popular_products as $product): ?>
        <div class="product-card">
          <div>
            <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
            <div class="product-seller">Sold by: <?php echo htmlspecialchars($product['business_name']); ?></div>
            <div class="product-price">
              <span class="original-price">₹<?php echo number_format($product['actual_price'], 2); ?></span>
              ₹<?php echo number_format($product['offered_price'], 2); ?>
            </div>
          </div>
          <div class="rating">
            Rate this:
            <select>
              <option>⭐</option>
              <option>⭐⭐</option>
              <option>⭐⭐⭐</option>
              <option>⭐⭐⭐⭐</option>
              <option>⭐⭐⭐⭐⭐</option>
            </select>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="product-list" id="searchResults" style="display: none;"></div>
    <div class="no-results" id="noResults" style="display: none;">No products found matching your search.</div>
  </div>

  <script>
    function searchProducts() {
      const query = document.getElementById('searchInput').value.trim();
      const businessId = document.getElementById('filterSelect').value;
      const category = document.getElementById('categorySelect').value;

      if (query.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('popularProducts').style.display = 'grid';
        return;
      }

      fetch(`search_products.php?query=${encodeURIComponent(query)}&business_id=${businessId}&category=${category}`)
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('searchResults');
          container.innerHTML = '';
          document.getElementById('noResults').style.display = data.length ? 'none' : 'block';
          document.getElementById('popularProducts').style.display = 'none';

          data.forEach(product => {
            container.innerHTML += `
              <div class="product-card">
                <div>
                  <div class="product-name">${product.product_name}</div>
                  <div class="product-seller">Sold by: ${product.business_name}</div>
                  <div class="product-price">
                    <span class="original-price">₹${parseFloat(product.actual_price).toFixed(2)}</span>
                    ₹${parseFloat(product.offered_price).toFixed(2)}
                  </div>
                </div>
                <div class="rating">
                  Rate this:
                  <select>
                    <option>⭐</option>
                    <option>⭐⭐</option>
                    <option>⭐⭐⭐</option>
                    <option>⭐⭐⭐⭐</option>
                    <option>⭐⭐⭐⭐⭐</option>
                  </select>
                </div>
              </div>`;
          });

          container.style.display = 'grid';
        });
    }
  </script>
</body>
</html>
    