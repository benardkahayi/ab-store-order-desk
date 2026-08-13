<?php
session_start();
require 'db.php';
if(!isset($_SESSION['admin'])){ header("Location: admin_login.php"); exit; }

$id = $_GET['id'];
$action = $_GET['action'];

if($action=='approve'){
    $conn->prepare("UPDATE refunds SET status='Approved' WHERE id=?")->execute([$id]);
} elseif($action=='reject'){
    $conn->prepare("UPDATE refunds SET status='Rejected' WHERE id=?")->execute([$id]);
}
header("Location: refunds.php");
