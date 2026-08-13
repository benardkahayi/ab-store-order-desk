<?php
session_start();
$pdo = new PDO("mysql:host=localhost;dbname=order_desk", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $id = $_POST['id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE refunds SET status = ?, response_date = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
}
?>
