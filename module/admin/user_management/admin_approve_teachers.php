<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

$success = "";
$error = "";

// Xử lý duyệt tài khoản nếu admin bấm approve
if (isset($_GET['approve_id'])) {
    $approve_id = intval($_GET['approve_id']);
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND role = 'teacher'");
    $stmt->bind_param("i", $approve_id);
    if ($stmt->execute()) {
        $success = "Teacher approved successfully.";
    } else {
        $error = "Failed to approve teacher.";
    }
    $stmt->close();
    // Redirect to clear the GET parameter and prevent re-approval on refresh
    header("Location: admin_approve_teachers.php?status=" . (empty($success) ? 'error' : 'success'));
    exit;
}

// Check for status messages from redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $success = "Teacher approved successfully.";
    } elseif ($_GET['status'] === 'error') {
        $error = "Failed to approve teacher.";
    }
}

// Lấy danh sách teacher pending
$result = $conn->query("SELECT id, fullname, email FROM users WHERE role = 'teacher' AND status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Teachers | BTEC FPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
    <link rel="stylesheet" href="../../../css/admin/admin_approve_teachers.css">
    
</head>
<body>

    <?php include "../../../includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="admin-page-header">
            <h2><i class="fas fa-user-check"></i> Approve Pending Teachers</h2>
        </div>

        <div class="course-management-overview">
            <h3><i class="fas fa-list-alt"></i> Pending Teacher Accounts</h3>
            <div class="course-management-content">
                <?php if (!empty($success)): ?>
                    <p class='success-message'><i class='fas fa-check-circle'></i> <?= $success ?></p>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <p class='error-message'><i class='fas fa-exclamation-triangle'></i> <?= $error ?></p>
                <?php endif; ?>

                <?php if ($result->num_rows > 0): ?>
                    <table class="enrollment-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <a class="approve-button" href="admin_approve_teachers.php?approve_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to approve this teacher?')">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-pending-teachers"><i class="fas fa-info-circle"></i> No pending teacher accounts at the moment.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="back-link">
            <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
        </div>

        <?php include "../../../includes/footer.php"; ?>
    </div>

    <script src="../../../js/sidebar.js"></script>
</body>
</html>