<?php
session_start();
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    $_SESSION['message_type'] = 'error';
    header('Location: ../index.php');
    exit();
}

$pdo = getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add new category
    if (isset($_POST['add_category'])) {
        $category_name = sanitizeInput($_POST['category_name']);
        
        if (!empty($category_name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$category_name]);
                
                $_SESSION['message'] = 'เพิ่มหมวดหมู่เรียบร้อยแล้ว';
                $_SESSION['message_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['message'] = 'เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่';
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = 'กรุณากรอกชื่อหมวดหมู่';
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Edit category
    if (isset($_POST['edit_category'])) {
        $category_id = (int)$_POST['category_id'];
        $category_name = sanitizeInput($_POST['category_name']);
        
        if (!empty($category_name) && $category_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([$category_name, $category_id]);
                
                $_SESSION['message'] = 'แก้ไขหมวดหมู่เรียบร้อยแล้ว';
                $_SESSION['message_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['message'] = 'เกิดข้อผิดพลาดในการแก้ไขหมวดหมู่';
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = 'ข้อมูลไม่ถูกต้อง';
            $_SESSION['message_type'] = 'error';
        }
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];
        
        if ($category_id > 0) {
            try {
                // Check if category has products
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_category WHERE category_id = ?");
                $stmt->execute([$category_id]);
                $product_count = $stmt->fetchColumn();
                
                if ($product_count > 0) {
                    $_SESSION['message'] = 'ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่ได้';
                    $_SESSION['message_type'] = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$category_id]);
                    
                    $_SESSION['message'] = 'ลบหมวดหมู่เรียบร้อยแล้ว';
                    $_SESSION['message_type'] = 'success';
                }
            } catch (PDOException $e) {
                $_SESSION['message'] = 'เกิดข้อผิดพลาดในการลบหมวดหมู่';
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['message'] = 'ข้อมูลไม่ถูกต้อง';
            $_SESSION['message_type'] = 'error';
        }
    }
}

// Redirect back to index page
header('Location: ../index.php');
exit();
?>