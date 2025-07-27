<?php
session_start();
require_once 'config.php';

// ถ้าล็อกอินแล้วให้กลับไปหน้าแรก
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    
    // ตรวจสอบข้อมูล
    if (empty($username) || empty($password) || empty($email)) {
        $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($password !== $confirm_password) {
        $error_message = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error_message = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        // ตรวจสอบว่าชื่อผู้ใช้หรืออีเมลซ้ำหรือไม่
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error_message = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว';
        } else {
            // เข้ารหัสรหัสผ่าน
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // บันทึกข้อมูลผู้ใช้
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $hashed_password, $email])) {
                $success_message = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Toom Tam Fishing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .register-header p {
            color: #666;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: bold;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .form-control {
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 5px;
        }
        .footer-links {
            text-align: center;
            margin-top: 20px;
        }
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>🎣 Toom Tam Fishing</h2>
            <p>สมัครสมาชิกเพื่อเริ่มต้นการซื้อสินค้า</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">อีเมล</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="form-text">รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร</div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">สมัครสมาชิก</button>
        </form>
        
        <div class="footer-links">
            <a href="login.php">เข้าสู่ระบบ</a> |
            <a href="index.php">กลับหน้าแรก</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>