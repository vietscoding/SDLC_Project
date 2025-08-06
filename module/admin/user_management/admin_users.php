<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../common/login.php");
    exit;
}

include "../../../includes/db_connect.php";

// Xử lý search nếu có
$keyword = $_GET['keyword'] ?? "";

// Hàm lấy user theo role + keyword
function getUsersByRole($conn, $role, $keyword) {
    if (!empty($keyword)) {
        $stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE role = ? AND (id LIKE ? OR fullname LIKE ? OR email LIKE ?) ORDER BY id DESC");
        $like = '%' . $keyword . '%';
        $stmt->bind_param("ssss", $role, $like, $like, $like);
    } else {
        $stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE role = ? ORDER BY id DESC");
        $stmt->bind_param("s", $role);
    }
    $stmt->execute();
    return $stmt->get_result();
}

$students = getUsersByRole($conn, 'student', $keyword);
$teachers = getUsersByRole($conn, 'teacher', $keyword);
$admins   = getUsersByRole($conn, 'admin', $keyword);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users (Admin) | BTEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/style.css">
     <link rel="stylesheet" href="../../../css/admin/admin_users.css">
     
</head>
<body>

<?php include "../../../includes/sidebar.php"; ?>

<div class="main-content">
    <div class="admin-page-header">
        <h2><i class="fas fa-users"></i> Manage Users</h2>
    </div>

    <div class="user-management-overview">
        <h3><i class="fas fa-search"></i> Search Users</h3>
        <div class="search-container">
            <form method="get" class="search-form">
                <input type="text" name="keyword" placeholder="Search by ID, name or email..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($keyword)): ?>
                    <a href="admin_users.php"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="user-list-section">
            <h3><i class="fas fa-user-graduate"></i> Students</h3>
            <?php if ($students->num_rows > 0): ?>
                <ul class="user-list">
                    <?php while ($row = $students->fetch_assoc()): ?>
                        <li class="user-item">
                            <div class="user-info">
                                <strong>ID:</strong> <?= $row['id'] ?> - <strong>Name:</strong> <?= htmlspecialchars($row['fullname']) ?> (<strong>Email:</strong> <?= htmlspecialchars($row['email']) ?>)
                            </div>
                            <div class="user-actions">
                                <a href="admin_edit_user.php?user_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                <a href="admin_delete_user.php?user_id=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-users"><i class="fas fa-exclamation-circle"></i> No students found.</p>
            <?php endif; ?>
        </div>

        <div class="user-list-section">
            <h3><i class="fas fa-chalkboard-teacher"></i> Teachers</h3>
            <?php if ($teachers->num_rows > 0): ?>
                <ul class="user-list">
                    <?php while ($row = $teachers->fetch_assoc()): ?>
                        <li class="user-item">
                            <div class="user-info">
                                <strong>ID:</strong> <?= $row['id'] ?> - <strong>Name:</strong> <?= htmlspecialchars($row['fullname']) ?> (<strong>Email:</strong> <?= htmlspecialchars($row['email']) ?>)
                            </div>
                            <div class="user-actions">
                                <a href="admin_edit_user.php?user_id=<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                <a href="admin_delete_user.php?user_id=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')"><i class="fas fa-trash-alt"></i> Delete</a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-users"><i class="fas fa-exclamation-circle"></i> No teachers found.</p>
            <?php endif; ?>
        </div>

        <div class="user-list-section admin-section">
            <h3><i class="fas fa-user-cog"></i> Admins</h3>
            <?php if ($admins->num_rows > 0): ?>
                <ul class="user-list">
                    <?php while ($row = $admins->fetch_assoc()): ?>
                        <li class="user-item">
                            <div class="user-info">
                                <strong>ID:</strong> <?= $row['id'] ?> - <strong>Name:</strong> <?= htmlspecialchars($row['fullname']) ?> (<strong>Email:</strong> <?= htmlspecialchars($row['email']) ?>)
                            </div>
                            <div class="user-actions">
                                <span style="color: var(--text-dark-secondary); font-style: italic;"><i class="fas fa-ban"></i> Cannot edit/delete admin user</span>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="no-users"><i class="fas fa-exclamation-circle"></i> No admins found.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="back-to-dashboard">
        <a href="../dashboard/admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php include "../../../includes/footer.php"; ?>
</div>

<script src="../../../js/sidebar.js"></script>
</body>
</html>