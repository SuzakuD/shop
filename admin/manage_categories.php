<?php
session_start();
require_once '../config.php';

// Require admin access
requireAdmin();

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if ($name && $description) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    $message = 'Category added successfully!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error adding category: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = 'Please fill in all fields.';
                $message_type = 'danger';
            }
            break;
            
        case 'edit':
            $id = (int)($_POST['id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if ($id && $name && $description) {
                try {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $id]);
                    $message = 'Category updated successfully!';
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = 'Error updating category: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = 'Please fill in all fields.';
                $message_type = 'danger';
            }
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id) {
                try {
                    // Check if category has products
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                    $stmt->execute([$id]);
                    $product_count = $stmt->fetchColumn();
                    
                    if ($product_count > 0) {
                        $message = 'Cannot delete category with existing products. Please move or delete products first.';
                        $message_type = 'danger';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->execute([$id]);
                        $message = 'Category deleted successfully!';
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = 'Error deleting category: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
            break;
    }
    
    // Store message in session and redirect to prevent form resubmission
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    header('Location: ../index.php');
    exit;
}
?>