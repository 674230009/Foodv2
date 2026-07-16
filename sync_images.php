<?php
require_once 'db.php';

// 📂 1. ระบุชื่อโฟลเดอร์ที่คุณเก็บรูปภาพไว้
$folder_name = 'images'; 

if (!is_dir($folder_name)) {
    die("❌ ไม่พบโฟลเดอร์ชื่อ '$folder_name' กรุณาสร้างโฟลเดอร์นี้ไว้ข้างๆ ไฟล์เว็บด้วยครับ");
}

// ล้างข้อมูลเก่าในตารางรูปภาพออกก่อนเพื่ออัปเดตใหม่
$pdo->query("TRUNCATE TABLE food_images");

// 2. ดึงเมนูอาหารทั้งหมดจากตาราง foods ออกมาแมตช์
$foods = $pdo->query("SELECT id, name_th FROM foods")->fetchAll();

echo "<h3>🔄 กำลังระบบซิงค์รูปภาพอัตโนมัติจากโฟลเดอร์ '$folder_name' ...</h3><hr>";

$success_count = 0;
$extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'PNG'];

foreach ($foods as $food) {
    $food_id = $food['id'];
    $food_name = trim($food['name_th']); // ตัดช่องว่างส่วนเกินออก
    $found_image = false;
    
    foreach ($extensions as $ext) {
        $image_file_name = $food_name . '.' . $ext;
        $full_path = $folder_name . '/' . $image_file_name;
        
        // 🛠️ แก้ไขจุดนี้: แปลงรหัสชื่อไฟล์ภาษาไทยเพื่อรองรับ Windows (ป้องกันปัญหามองไม่เห็นไฟล์ภาษาไทย)
        $windows_path = iconv('UTF-8', 'TIS-620//IGNORE', $full_path);
        
        // ตรวจสอบไฟล์ (ลองเช็กทั้งแบบปกติ และแบบแปลงรหัสภาษาไทย)
        if (file_exists($full_path) || (@file_exists($windows_path))) {
            
            // 💾 3. บันทึกดึงรูปไปใส่ตารางในฐานข้อมูล
            $stmt = $pdo->prepare("INSERT INTO food_images (food_id, image_name) VALUES (?, ?)");
            $stmt->execute([$food_id, $image_file_name]);
            
            echo "<span style='color: green;'>✅ จับคู่สำเร็จ:</span> เมนู <b>$food_name</b> -> <i>$image_file_name</i><br>";
            $found_image = true;
            $success_count++;
            break; 
        }
    }
    
    if (!$found_image) {
        echo "<span style='color: red;'>❌ ไม่พบรูปภาพ:</span> เมนู <b>$food_name</b> (ต้องมีไฟล์ชื่อ <span style='background:#fff3cd; padding:2px 5px;'>$food_name.jpg</span> หรือนามสกุลอื่นอยู่ในโฟลเดอร์)<br>";
    }
}

echo "<hr><b>🎉 เสร็จสิ้น! นำชื่อรูปภาพเข้าตารางสำเร็จทั้งหมด $success_count เมนู</b>";
echo "<br><br><a href='index.php' style='display:inline-block; padding:8px 15px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:4px;'>👉 คลิกที่นี่เพื่อกลับไปดูตารางอาหาร</a>";
?>