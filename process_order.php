<?php
require_once 'includes/config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Validate and sanitize input
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$customer_name = trim(filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_STRING));
$phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
$address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING));
$taille = trim(filter_input(INPUT_POST, 'taille', FILTER_SANITIZE_STRING));
$color = trim(filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING));
$delivery_choice = trim(filter_input(INPUT_POST, 'delivery_choice', FILTER_SANITIZE_STRING));
$wilaya = trim(filter_input(INPUT_POST, 'wilaya', FILTER_SANITIZE_STRING));

// Validate required fields
if (!$product_id || empty($customer_name) || empty($phone) || empty($address) || 
    empty($taille) || empty($color) || empty($delivery_choice) || empty($wilaya)) {
    die("All fields are required. Please go back and fill in all fields.");
}

// Verify product exists
$stmt = $connection->prepare("SELECT id FROM products WHERE id = :id");
$stmt->bindValue(':id', $product_id, SQLITE3_INTEGER);
$result = $stmt->execute();

if (!$result->fetchArray(SQLITE3_ASSOC)) {
    die("Invalid product selected.");
}

try {
    // Validate delivery choice
    if (!in_array($delivery_choice, ['domicile', 'bureau_noest'])) {
        die("Invalid delivery choice. Please go back and select a valid delivery option.");
    }

    // Insert order into database
    $stmt = $connection->prepare("
        INSERT INTO orders (customer_name, phone, address, product_id, taille, color, delivery_choice, wilaya, status) 
        VALUES (:customer_name, :phone, :address, :product_id, :taille, :color, :delivery_choice, :wilaya, 'pending')
    ");
    
    $stmt->bindValue(':customer_name', $customer_name, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':address', $address, SQLITE3_TEXT);
    $stmt->bindValue(':product_id', $product_id, SQLITE3_INTEGER);
    $stmt->bindValue(':taille', $taille, SQLITE3_TEXT);
    $stmt->bindValue(':color', $color, SQLITE3_TEXT);
    $stmt->bindValue(':delivery_choice', $delivery_choice, SQLITE3_TEXT);
    $stmt->bindValue(':wilaya', $wilaya, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        // Redirect back to product page with success message
        header("Location: product.php?id=" . $product_id . "&order=success");
        exit;
    } else {
        throw new Exception("Error processing order.");
    }
} catch (Exception $e) {
    // Log error and show detailed message during development
    error_log($e->getMessage());
    
    // Show detailed error message for debugging
    die("Error processing order: " . $e->getMessage() . " - " . $connection->lastErrorMsg());
}
