<?php
session_start();

//ensures user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../includes/db.php';
include '../includes/activity_logger.php';

//handling admin actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $user_id = $_POST['user_id'];
    $user_role = $_POST['user_role'];
    
    //setting flag is user is suspended
    if ($action === 'suspend') {
        if ($user_role === 'patient') {
            $sql = "UPDATE PATIENT_USERS SET is_suspended = 1 WHERE patient_id = $user_id";
        } elseif ($user_role === 'doctor') {
            $sql = "UPDATE DOCTOR_USERS SET is_suspended = 1 WHERE doctor_id = $user_id";
        }
        $conn->query($sql);
        
        //logging action
        logUserAction($conn, $_SESSION['user_id'], 'admin', 'USER_SUSPEND', "Suspended $user_role user ID: $user_id");
        
    //if account is reactivated, remove flag
    } elseif ($action === 'activate') {
        if ($user_role === 'patient') {
            $sql = "UPDATE PATIENT_USERS SET is_suspended = 0 WHERE patient_id = $user_id";
        } elseif ($user_role === 'doctor') {
            $sql = "UPDATE DOCTOR_USERS SET is_suspended = 0 WHERE doctor_id = $user_id";
        }
        $conn->query($sql);
        
        //logging the action
        logUserAction($conn, $_SESSION['user_id'], 'admin', 'USER_ACTIVATE', "Activated $user_role user ID: $user_id");

    //user deletion
    } elseif ($action === 'delete') {
        if ($user_role === 'patient') {
            //first delete from PATIENT_USERS, then PATIENT_INFO
            $sql = "DELETE FROM PATIENT_USERS WHERE patient_id = $user_id";
            $conn->query($sql);
            $sql = "DELETE FROM PATIENT_INFO WHERE patient_id = $user_id";
            $conn->query($sql);
        } elseif ($user_role === 'doctor') {
            //delete from DOCTOR_USERS first, then DOCTOR_INFO
            $sql = "DELETE FROM DOCTOR_USERS WHERE doctor_id = $user_id";
            $conn->query($sql);
            $sql = "DELETE FROM DOCTOR_INFO WHERE doctor_id = $user_id";
            $conn->query($sql);
        } elseif ($user_role === 'admin') {
            //delete admin from ADMIN_USERS
            $sql = "DELETE FROM ADMIN_USERS WHERE admin_id = $user_id";
            $conn->query($sql);
        }
        //logging the action
        logUserAction($conn, $_SESSION['user_id'], 'admin', 'USER_DELETE', "Deleted $user_role user ID: $user_id");
    //if adding user
    } elseif ($action === 'add_user') {
        $user_role = $_POST['user_role'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        if ($user_role === 'doctor') {
            //inserting doctors into DOCTOR_INFO
            $sql = "INSERT INTO DOCTOR_INFO (first_name, last_name, contact_email) VALUES ('$first_name', '$last_name', '$email')";
            $conn->query($sql);
            $doctor_id = $conn->insert_id;
            
            //then DOCTOR_USERS
            $sql = "INSERT INTO DOCTOR_USERS (doctor_id, login_email, password_hash) VALUES ($doctor_id, '$email', '$password_hash')";
            $conn->query($sql);
        } elseif ($user_role === 'admin') {
            //inserting admins into ADMIN_USERS
            $sql = "INSERT INTO ADMIN_USERS (username, password_hash, email, full_name) VALUES ('$first_name', '$password_hash', '$email', '$first_name $last_name')";
            $conn->query($sql);
        }
        
        //logging the action
        logUserAction($conn, $_SESSION['user_id'], 'admin', 'USER_CREATE', "Created new $user_role user: $first_name $last_name");
    }
    
    header("Location: users.php");
    exit();
}

//for storing all users
$users = [];

//getting all patients alphabetically
$result = $conn->query("
    SELECT p.patient_id as user_id, p.first_name, p.last_name, p.contact_email, 
           pu.created_at, pu.is_suspended, 'patient' as role
    FROM PATIENT_INFO p 
    JOIN PATIENT_USERS pu ON p.patient_id = pu.patient_id
    ORDER BY p.first_name, p.last_name
");

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

//getting doctors alphabetically
$result = $conn->query("
    SELECT d.doctor_id as user_id, d.first_name, d.last_name, d.contact_email, 
           du.created_at, du.is_suspended, 'doctor' as role
    FROM DOCTOR_INFO d 
    JOIN DOCTOR_USERS du ON d.doctor_id = du.doctor_id
    ORDER BY d.first_name, d.last_name
");

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

//getting admins alphabetically, not including oneself
$current_admin_id = $_SESSION['user_id'];
$result = $conn->query("
    SELECT admin_id as user_id, full_name as first_name, '' as last_name, email as contact_email, 
           created_at, is_active, 'admin' as role
    FROM ADMIN_USERS
    WHERE admin_id != $current_admin_id
    ORDER BY full_name
");

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../css/styles.css?v=5">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1100px;
            margin: 3.5rem auto 2rem;
            padding: 0 20px 20px;
        }
        
        .admin-header {
            background: var(--card);
            color: var(--text-primary);
            padding: 24px 24px 22px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid rgba(148, 163, 184, 0.4);
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .admin-nav {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .nav-button {
            background: var(--primary);
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.15s ease;
        }
        
        .nav-button:hover {
            background: #224764;
        }
        
        .nav-button.secondary {
            background: #6b7280;
        }
        
        .nav-button.danger {
            background: #b91c1c;
        }
        
        .users-table {
            margin-top: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-patient {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .role-doctor {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .role-admin {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-suspended {
            color: #dc3545;
            font-weight: 500;
        }
        
        .status-active {
            color: #28a745;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-suspend {
            background: #ffc107;
            color: #000;
        }
        
        .btn-activate {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .action-select {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            min-width: 120px;
        }
        
        .add-user-section {
            background: var(--surface-color);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            margin-bottom: 20px;
        }
  
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group label {
            font-weight: 500;
            color: #333;
        }
        
        .form-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .add-user-form {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-wrap: nowrap;
}

.add-user-form .form-group {
    flex: 0 0 auto;
}

.add-user-form input {
    width: 150px;
}

.btn-add {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;

    padding: 6px 6px;
    width:100px;
    height: 35px;
    align-self: flex-end;

    transform: translateY(17px);
}

#user_role {
    transform: translateY(1px);
}
        
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="admin-container">
        <div class="admin-header">
            <h1>User Management</h1>
        </div>
        
        <div class="admin-nav">
            <a href="dashboard.php" class="nav-button">Dashboard</a>
            <a href="activity.php" class="nav-button">Activity Logs</a>
            <a href="reports.php" class="nav-button">System Reports</a>
            <a href="../logout.php" class="nav-button danger">Logout</a>
        </div>
        
        <div class="add-user-section">
            <h3>Add New User</h3>
            <form method="POST" class="add-user-form">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label for="user_role">User Type</label>
                    <select name="user_role" id="user_role" required>
                        <option value="">Select Type</option>
                        <option value="doctor">Doctor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <button type="submit" class="btn-add">Add User</button>
                <a href="/healthcare/register.php"><button type="button" class="btn-add">Add Patient</button></a>
            </form>
        </div>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['contact_email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="status-<?php echo $user['is_active'] ? 'active' : 'suspended'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-<?php echo $user['is_suspended'] ? 'suspended' : 'active'; ?>">
                                        <?php echo $user['is_suspended'] ? 'Suspended' : 'Active'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <select onchange="handleUserAction(this, <?php echo $user['user_id']; ?>, '<?php echo $user['role']; ?>', <?php echo ($user['role'] === 'admin' ? $user['is_active'] : $user['is_suspended']) ? 'true' : 'false'; ?>)">
                                        <option value="">Select Action</option>
                                        <option value="delete" style="color: #dc3545;">Delete User</option>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <?php if ($user['is_active']): ?>
                                                <option value="suspend">Suspend</option>
                                            <?php else: ?>
                                                <option value="activate">Activate</option>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($user['is_suspended']): ?>
                                                <option value="activate">Activate</option>
                                            <?php else: ?>
                                                <option value="suspend">Suspend</option>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    
    <script>
        //javascript function for processing user actions in the dropdown
        function handleUserAction(selectElement, userId, userRole, isSuspended) {
            const action = selectElement.value;
            if (!action) return;
            
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete this user? This action cannot be undone')) {
                    selectElement.value = '';
                    return;
                }
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'user_id';
            userIdInput.value = userId;
            
            const userRoleInput = document.createElement('input');
            userRoleInput.type = 'hidden';
            userRoleInput.name = 'user_role';
            userRoleInput.value = userRole;
            
            form.appendChild(actionInput);
            form.appendChild(userIdInput);
            form.appendChild(userRoleInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
