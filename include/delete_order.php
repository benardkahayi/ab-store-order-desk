<?php
session_start();
require 'db.php';
if(!isset($_SESSION['admin'])) { header("Location: admin_login.php"); exit; }

$id = $_GET['id'];
$conn->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
header("Location: orders.php");
