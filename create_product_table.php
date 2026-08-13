<?php
session_start();
require_once __DIR__ . '/db.php';

try {
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        description TEXT,
        category VARCHAR(100),
        status ENUM('active', 'inactive') DEFAULT 'active',
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "✅ Table 'products' created successfully!<br>";
    
    // Check if products exist
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert sample products
        $sql = "INSERT INTO products (name, price, stock, description, category, status) VALUES
            ('Men\'s T-Shirt', 25000, 100, 'Cotton t-shirt, available in multiple colors. High quality fabric.', 'Clothing', 'active'),
            ('Women\'s Dress', 75000, 50, 'Elegant summer dress perfect for any occasion.', 'Clothing', 'active'),
            ('Sneakers', 120000, 30, 'Comfortable running shoes with good grip.', 'Footwear', 'active'),
            ('Leather Handbag', 85000, 25, 'Genuine leather handbag with multiple compartments.', 'Accessories', 'active'),
            ('Sunglasses', 45000, 60, 'UV protection sunglasses. Stylish and durable.', 'Accessories', 'active'),
            ('Jeans Pants', 55000, 80, 'Classic fit denim jeans.', 'Clothing', 'active'),
            ('Wrist Watch', 150000, 15, 'Elegant wrist watch with leather strap.', 'Accessories', 'active'),
            ('Backpack', 95000, 40, 'Waterproof backpack for daily use.', 'Bags', 'active')";
        
        $conn->exec($sql);
        echo "✅ Sample products inserted successfully!<br>";
    } else {
        echo "ℹ️ Products already exist in database.<br>";
    }
    
    // Show products
    $products = $conn->query("SELECT * FROM products")->fetchAll();
    echo "<h3>Products in Database:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Category</th><th>Status</th></tr>";
    foreach ($products as $p) {
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['name']}</td>";
        echo "<td>TZS " . number_format($p['price'], 0) . "</td>";
        echo "<td>{$p['stock']}</td>";
        echo "<td>{$p['category']}</td>";
        echo "<td>{$p['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<a href='products.php' class='btn btn-primary'>Go to Products Page →</a>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>