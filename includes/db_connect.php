<?php
$host = "localhost";
$user = "root";
$pass = "1234"; // nếu Workbench đặt mật khẩu thì ghi vào đây
$dbname = "lms";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
