<?php
/**
 * SHOPKEY - Database Import Script
 * Chạy 1 lần để import database, xong thì XÓA file này!
 */

// Bảo mật: cần key đúng mới chạy được
$secret_key = 'shopkey_import_2026';
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die('❌ Không có quyền truy cập. Thêm ?key=shopkey_import_2026 vào URL');
}

// Thông tin kết nối (lấy từ biến môi trường Render)
$host     = getenv('DB_HOST')     ?: 'localhost';
$port     = getenv('DB_PORT')     ?: '3306';
$user     = getenv('DB_USERNAME') ?: getenv('DB_USER') ?: 'root';
$pass     = getenv('DB_PASSWORD') ?: '';
$dbname   = getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: 'shopkey';

echo "<pre style='font-family:monospace; padding:20px;'>";
echo "🔌 Đang kết nối tới: $host:$port / $dbname\n\n";

// Kết nối MySQL
$conn = new mysqli($host, $user, $pass, $dbname, (int)$port);
if ($conn->connect_error) {
    die("❌ Kết nối thất bại: " . $conn->connect_error);
}

echo "✅ Kết nối thành công!\n\n";

// Đọc file SQL (để cùng thư mục với import.php)
$sql_file = __DIR__ . '/shopkey.sql';
if (!file_exists($sql_file)) {
    die("❌ Không tìm thấy file shopkey.sql bên cạnh import.php!");
}

$sql_content = file_get_contents($sql_file);
echo "📄 Đã đọc file SQL (" . number_format(strlen($sql_content)) . " bytes)\n\n";

// Cài charset
$conn->set_charset('utf8mb4');

// Tắt kiểm tra foreign key tạm thời
$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("SET SQL_MODE=''");

// Tách từng câu SQL và chạy
$statements = [];
$delimiter = ';';
$lines = explode("\n", $sql_content);
$current = '';

foreach ($lines as $line) {
    $trimmed = trim($line);
    // Bỏ comment
    if (substr($trimmed, 0, 2) === '--' || $trimmed === '') continue;
    $current .= $line . "\n";
    if (substr(rtrim($line), -1) === ';') {
        $stmt = trim($current);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
        $current = '';
    }
}

$total     = count($statements);
$success   = 0;
$errors    = [];

echo "📋 Tổng số câu lệnh SQL: $total\n";
echo str_repeat('-', 50) . "\n";

foreach ($statements as $i => $stmt) {
    // Bỏ qua các lệnh /*!...*/ không cần thiết
    if (preg_match('/^\/\*!/', $stmt)) continue;

    if ($conn->query($stmt) === TRUE) {
        $success++;
    } else {
        $errors[] = [
            'no'  => $i + 1,
            'sql' => substr($stmt, 0, 120) . '...',
            'err' => $conn->error,
        ];
    }
}

// Bật lại foreign key check
$conn->query("SET FOREIGN_KEY_CHECKS=1");

echo "\n✅ Thành công: $success / $total câu lệnh\n";

if (count($errors) > 0) {
    echo "\n⚠️  Lỗi " . count($errors) . " câu lệnh:\n";
    foreach ($errors as $e) {
        echo "  [#{$e['no']}] {$e['err']}\n";
        echo "       SQL: {$e['sql']}\n\n";
    }
} else {
    echo "\n🎉 Import hoàn tất, không có lỗi!\n";
    echo "👉 Hãy XÓA file import.php và shopkey.sql khỏi repo ngay!\n";
}

$conn->close();
echo "</pre>";