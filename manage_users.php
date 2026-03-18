<?php
session_start();
include 'db.php';

// Security: Only Admins can see this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Handle User Deletion
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $conn->query("DELETE FROM users WHERE id = $id");
    header("Location: manage_users.php?msg=User Deleted");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Goldrich</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #34495e; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #112941; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .role-admin { background: #ffeaa7; color: #d63031; }
        .role-staff { background: #dfe6e9; color: #2d3436; }
        .delete-btn { color: #e74c3c; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-btn">⬅ Back to Dashboard</a>
    
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>⚙️ User Management</h2>
        <a href="register.php" style="background: #27ae60; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 13px;">+ Create New User</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <p style="color: green; font-weight: bold;"><?php echo $_GET['msg']; ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT id, username, role FROM users");
            while ($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><strong><?php echo strtoupper($row['username']); ?></strong></td>
                <td>
                    <span class="role-badge <?php echo ($row['role'] == 'Admin') ? 'role-admin' : 'role-staff'; ?>">
                        <?php echo $row['role']; ?>
                    </span>
                </td>
                <td>
                    <?php if ($row['username'] !== $_SESSION['username']): ?>
                        <a href="manage_users.php?delete_id=<?php echo $row['id']; ?>" 
                           class="delete-btn" 
                           onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    <?php else: ?>
                        <span style="color: #ccc; font-style: italic;">(You)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>