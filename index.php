<?php
require_once 'db.php';

// จัดการการลบข้อมูล (Delete)
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: index.php");
    exit;
}

// =========================================================================
// 🔄 ระบบ AUTO SYNC & GENERATE รูปภาพอัตโนมัติทุกครั้งที่เปิดหน้านี้
// =========================================================================
$folder_name = 'images'; 
$extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'PNG'];

// สร้างโฟลเดอร์ให้อัตโนมัติถ้าไม่มี
if (!is_dir($folder_name)) {
    mkdir($folder_name, 0777, true);
}

// 1. ดึงข้อมูลอาหารจากฐานข้อมูลเพื่อมาตรวจสอบรูป
$all_foods = $pdo->query("SELECT id, name_th FROM foods")->fetchAll();

// 2. ล้างตารางเก่าเพื่อเคลียร์ข้อมูลรูปภาพให้เป็นปัจจุบันที่สุด
$pdo->query("TRUNCATE TABLE food_images");

// 3. วนลูปเช็กไฟล์รูปภาพ
foreach ($all_foods as $f) {
    $f_id = $f['id'];
    $f_name = trim($f['name_th']);
    $found = false;
    $img_name = $f_name . '.jpg'; // กำหนดนามสกุลหลักไว้ก่อน
    
    foreach ($extensions as $ext) {
        $check_name = $f_name . '.' . $ext;
        $full_path = $folder_name . '/' . $check_name;
        $windows_path = iconv('UTF-8', 'TIS-620//IGNORE', $full_path);
        
        if (file_exists($full_path) || (@file_exists($windows_path))) {
            $img_name = $check_name;
            $found = true;
            break; 
        }
    }
    
    // ✨ [ส่วนที่เพิ่มเข้ามา]: ถ้าค้นหาในโฟลเดอร์แล้วไม่เจอรูปภาพใดๆ เลย ระบบจะสร้างรูปภาพตัวอย่างให้ทันที!
    if (!$found) {
        $target_path = $folder_name . '/' . $f_name . '.jpg';
        
        // ใช้คำสั่งขอยืมรูปภาพอาหารน่ารักๆ จาก Unsplash Source มาบันทึกลงโฟลเดอร์ให้เองอัตโนมัติ
        $image_url = "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&auto=format&fit=crop"; 
        $image_data = @file_get_contents($image_url);
        
        if ($image_data) {
            // เซฟไฟล์ลงโฟลเดอร์จริงในเครื่อง
            @file_put_contents($target_path, $image_data);
        }
    }
    
    // บันทึกเข้าฐานข้อมูลคู่กับไอดีอาหาร
    $stmtImg = $pdo->prepare("INSERT INTO food_images (food_id, image_name) VALUES (?, ?)");
    $stmtImg->execute([$f_id, $img_name]);
}
// =========================================================================

// 📋 ดึงข้อมูลอาหารพร้อมเชื่อมตารางรูปภาพ
$sql = "SELECT foods.*, food_images.image_name 
        FROM foods 
        LEFT JOIN food_images ON foods.id = food_images.food_id 
        ORDER BY foods.id DESC";
$foods = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการข้อมูลอาหารและสูตรอาหาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-secondary">📋 รายการเมนูอาหารทั้งหมด</h2>
        <a href="manage.php" class="btn btn-primary">+ เพิ่มเมนูอาหารใหม่</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 15%">รูปภาพเมนู</th>
                        <th style="width: 20%">ชื่ออาหาร (ไทย)</th>
                        <th style="width: 15%">หมวดหมู่</th>
                        <th style="width: 35%">วัตถุดิบและส่วนผสม (Recipe)</th>
                        <th style="width: 15%" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($foods)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูลอาหารในระบบ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($foods as $food): 
                            $stmtRecipe = $pdo->prepare("SELECT * FROM recipes WHERE food_id = ?");
                            $stmtRecipe->execute([$food['id']]);
                            $recipes = $stmtRecipe->fetchAll();
                        ?>
                            <tr>
                                <td>
                                    <?php 
                                    $image_name = $food['image_name'];
                                    $image_path = '';

                                    if (!empty($image_name)) {
                                        if (file_exists('images/' . $image_name)) {
                                            $image_path = 'images/' . $image_name;
                                        } elseif (file_exists('uploads/' . $image_name)) {
                                            $image_path = 'uploads/' . $image_name;
                                        }
                                    }
                                    
                                    if (!empty($image_path)): 
                                    ?>
                                        <img src="<?= $image_path ?>" class="img-thumbnail" style="width: 120px; height: 85px; object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-danger small fw-bold">❌ หาไฟล์ไม่เจอ</span><br>
                                        <span class="text-muted" style="font-size:10px;">(ระบบกำลังโหลดรูปเริ่มต้น...)</span>
                                    <?php endif; ?>
                                </td>

                                <td><strong><?= htmlspecialchars($food['name_th']) ?></strong></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($food['category']) ?></span></td>
                                <td>
                                    <?php if (!empty($recipes)): ?>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($recipes as $r): ?>
                                                <li><?= htmlspecialchars($r['recipe_name']) ?> <?= $r['quantity'] ?> <?= htmlspecialchars($r['unit_name']) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted small">ไม่มีข้อมูลวัตถุดิบ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="manage.php?id=<?= $food['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                    <a href="index.php?delete_id=<?= $food['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบเมนูนี้? ข้อมูลวัตถุดิบทั้งหมดจะถูกลบไปด้วย');">ลบ</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>