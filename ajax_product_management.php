<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$action = $_POST['action'] ?? '';
$pdo = getConnection();

try {
    switch ($action) {
        case 'delete':
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id <= 0) {
                throw new Exception('Product ID ไม่ถูกต้อง');
            }
            
            // Check if product exists in any orders (to prevent deletion)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $order_count = $stmt->fetchColumn();
            
            if ($order_count > 0) {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบสินค้าที่มีคำสั่งซื้อแล้ว']);
                exit;
            }
            
            // Delete from product_category first
            $stmt = $pdo->prepare("DELETE FROM product_category WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            // Delete product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            
            echo json_encode(['success' => true, 'message' => 'ลบสินค้าเรียบร้อยแล้ว']);
            break;
            
        case 'get':
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id <= 0) {
                throw new Exception('Product ID ไม่ถูกต้อง');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception('ไม่พบสินค้า');
            }
            
            // Get categories for this product
            $stmt = $pdo->prepare("SELECT category_id FROM product_category WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $product['categories'] = $categories;
            
            echo json_encode(['success' => true, 'product' => $product]);
            break;
            
        case 'save':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $categories = $_POST['categories'] ?? [];
            
            if (empty($name) || $price <= 0) {
                throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
            
            if ($product_id > 0) {
                // Update existing product
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?");
                $stmt->execute([$name, $description, $price, $stock, $product_id]);
                $message = 'แก้ไขสินค้าเรียบร้อยแล้ว';
            } else {
                // Add new product
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $stock]);
                $product_id = $pdo->lastInsertId();
                $message = 'เพิ่มสินค้าเรียบร้อยแล้ว';
            }
            
            // Update categories
            $stmt = $pdo->prepare("DELETE FROM product_category WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            if (!empty($categories)) {
                $stmt = $pdo->prepare("INSERT INTO product_category (product_id, category_id) VALUES (?, ?)");
                foreach ($categories as $category_id) {
                    $stmt->execute([$product_id, (int)$category_id]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => $message, 'product_id' => $product_id]);
            break;
            
        default:
            throw new Exception('Action ไม่ถูกต้อง');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>