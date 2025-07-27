<?php
session_start();

// ล้างข้อมูล session
session_unset();
session_destroy();

// กลับไปหน้าแรก
header("Location: index.php");
exit();
?>