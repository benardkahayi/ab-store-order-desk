<?php
$host = "localhost";
$dbname = "order_desk_main";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Delete existing admin
    $conn->exec("DELETE FROM admins WHERE username = 'admin'");
    echo "✅ Admin existing amefutwa<br>";
    
    // Create new admin with password 'admin123'
    $new_password = 'admin123';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO admins (username, password, name) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $hashed_password, 'System Administrator']);
    
    echo "✅ Admin mpya ameundwa!<br>";
    echo "<hr>";
    echo "<h3>Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "<li><strong>Hash:</strong> " . $hashed_password . "</li>";
    echo "</ul>";
    
    // Test verification
    $test = password_verify('admin123', $hashed_password);
    echo "<br><strong>Test verification:</strong> " . ($test ? "✅ Password verification successful!" : "❌ Failed");
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>