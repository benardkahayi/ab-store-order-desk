<?php
function getOrderByOrderIdAndPhone(PDO $pdo, string $order_id, string $phone): ?array {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND phone = ? LIMIT 1");
    $stmt->execute([$order_id, $phone]);
    return $stmt->fetch() ?: null;
}
