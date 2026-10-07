<?php
session_start();

//ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../includes/db.php';

//get statistics for reports
$reports = [];

//getting total patients
$result = $conn->query("SELECT COUNT(*) as count FROM PATIENT_USERS");
$reports['total_patients'] = $result->fetch_assoc()['count'];

//same for doctors
$result = $conn->query("SELECT COUNT(*) as count FROM DOCTOR_USERS");
$reports['total_doctors'] = $result->fetch_assoc()['count'];

//same for active admins
$result = $conn->query("SELECT COUNT(*) as count FROM ADMIN_USERS WHERE is_active=1");
$reports['total_admins'] = $result->fetch_assoc()['count'];

//querying and counting total hospital visits
$result = $conn->query("SELECT COUNT(*) as count FROM HOSPITAL_VISITS");
$reports['total_visits'] = $result->fetch_assoc()['count'];

//querying and counting total hospital visits in past 30 days
$result = $conn->query("SELECT COUNT(*) as count FROM HOSPITAL_VISITS WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$reports['visits_last_30_days'] = $result->fetch_assoc()['count'];

//querying and counting total hospital visits in past 7 days
$result = $conn->query("SELECT COUNT(*) as count FROM HOSPITAL_VISITS WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$reports['visits_last_7_days'] = $result->fetch_assoc()['count'];

//querying and counting total activities in past 24 hours
$result = $conn->query("SELECT COUNT(*) as count FROM ACTIVITY_LOG WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$reports['activities_last_24h'] = $result->fetch_assoc()['count'];

//querying and counting total activities in past 7 days
$result = $conn->query("SELECT COUNT(*) as count FROM ACTIVITY_LOG WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$reports['activities_last_7_days'] = $result->fetch_assoc()['count'];

//most active users in the past 7 days
$result = $conn->query("
    SELECT user_id, user_role, COUNT(*) as activity_count 
    FROM ACTIVITY_LOG 
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY user_id, user_role 
    ORDER BY activity_count DESC 
    LIMIT 10
");
$reports['most_active_users'] = $result->fetch_all(MYSQLI_ASSOC);

//reporting recent patient and doctor registrations
$result = $conn->query("
    SELECT 'patient' as role, COUNT(*) as count 
    FROM PATIENT_USERS 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    UNION ALL
    SELECT 'doctor' as role, COUNT(*) as count 
    FROM DOCTOR_USERS 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$reports['recent_registrations'] = $result->fetch_all(MYSQLI_ASSOC);

//querying and counting suspended patients
$result = $conn->query("SELECT COUNT(*) as count FROM PATIENT_USERS WHERE is_suspended = 1");
$reports['suspended_patients'] = $result->fetch_assoc()['count'];

//querying and counting suspended doctors
$result = $conn->query("SELECT COUNT(*) as count FROM DOCTOR_USERS WHERE is_suspended = 1");
$reports['suspended_doctors'] = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports</title>
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
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .reports-grid-bottom {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .report-card {
            background: var(--surface-color);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.4);
        }
        
        .report-card h3 {
            margin-top: 0;
            color: var(--primary-color);
            border-bottom: 1px solid rgba(148, 163, 184, 0.6);
            padding-bottom: 8px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #666;
        }
        
        .stat-value {
            font-weight: 600;
            color: #333;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-user {
            font-weight: 500;
            color: #667eea;
        }
        
        .activity-count {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="admin-container">
        <div class="admin-header">
            <h1>System Reports</h1>
            <p>Comprehensive system analytics and health metrics</p>
        </div>
        
        <div class="admin-nav">
            <a href="dashboard.php" class="nav-button">Dashboard</a>
            <a href="users.php" class="nav-button">Manage Users</a>
            <a href="activity.php" class="nav-button">Activity Logs</a>
            <a href="../logout.php" class="nav-button danger">Logout</a>
        </div>
        
        <div class="reports-grid">
            <div class="report-card">
                <h3>User Statistics</h3>
                <div class="stat-item">
                    <span class="stat-label">Total Patients</span>
                    <span class="stat-value"><?php echo $reports['total_patients']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Doctors</span>
                    <span class="stat-value"><?php echo $reports['total_doctors']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Active Admins</span>
                    <span class="stat-value"><?php echo $reports['total_admins']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Suspended Patients</span>
                    <span class="stat-value"><?php echo $reports['suspended_patients']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Suspended Doctors</span>
                    <span class="stat-value"><?php echo $reports['suspended_doctors']; ?></span>
                </div>
            </div>
            
            <div class="report-card">
                <h3>Visit Statistics</h3>
                <div class="stat-item">
                    <span class="stat-label">Total Visits</span>
                    <span class="stat-value"><?php echo $reports['total_visits']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Last 30 Days</span>
                    <span class="stat-value"><?php echo $reports['visits_last_30_days']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Last 7 Days</span>
                    <span class="stat-value"><?php echo $reports['visits_last_7_days']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Avg per Day (30d)</span>
                    <span class="stat-value"><?php echo round($reports['visits_last_30_days'] / 30, 1); ?></span>
                </div>
            </div>
            
            <div class="report-card">
                <h3>System Activity</h3>
                <div class="stat-item">
                    <span class="stat-label">Activities (24h)</span>
                    <span class="stat-value"><?php echo $reports['activities_last_24h']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Activities (7d)</span>
                    <span class="stat-value"><?php echo $reports['activities_last_7_days']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Avg per Day (7d)</span>
                    <span class="stat-value"><?php echo round($reports['activities_last_7_days'] / 7, 1); ?></span>
                </div>
            </div>
        </div>
        
        <div class="reports-grid-bottom">
            <div class="report-card">
                <h3>Recent Registrations (30 days)</h3>
                <?php foreach ($reports['recent_registrations'] as $reg): ?>
                    <div class="stat-item">
                        <span class="stat-label"><?php echo ucfirst($reg['role']); ?>s</span>
                        <span class="stat-value"><?php echo $reg['count']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="report-card">
                <h3>Most Active Users (7 days)</h3>
                <div class="activity-list">
                    <?php if (empty($reports['most_active_users'])): ?>
                        <p>No activity data available.</p>
                    <?php else: ?>
                        <?php foreach ($reports['most_active_users'] as $user): ?>
                            <div class="activity-item">
                                <div class="activity-user">
                                    <?php echo ucfirst($user['user_role']); ?> #<?php echo $user['user_id']; ?>
                                </div>
                                <div class="activity-count">
                                    <?php echo $user['activity_count']; ?> activities
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
