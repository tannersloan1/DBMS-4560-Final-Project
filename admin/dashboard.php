<?php
session_start();

//ensure use is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../includes/db.php';

//get statistics
$stats = [];

//querying and counting total patients
$result = $conn->query("SELECT COUNT(*) as count FROM PATIENT_USERS");
$stats['total_patients'] = $result->fetch_assoc()['count'];

//same for doctors
$result = $conn->query("SELECT COUNT(*) as count FROM DOCTOR_USERS");
$stats['total_doctors'] = $result->fetch_assoc()['count'];

//same for active admins
$result = $conn->query("SELECT COUNT(*) as count FROM ADMIN_USERS WHERE is_active=1");
$stats['total_admins'] = $result->fetch_assoc()['count'];

//querying and counting total hospitalvisits
$result = $conn->query("SELECT COUNT(*) as count FROM HOSPITAL_VISITS");
$stats['total_visits'] = $result->fetch_assoc()['count'];

//querying and counting total hospital visits this week
$result = $conn->query("SELECT COUNT(*) as count FROM HOSPITAL_VISITS WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['visits_this_week'] = $result->fetch_assoc()['count'];

//average vists per week
$result = $conn->query("SELECT COUNT(*) as count FROM HOSPITAL_VISITS WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 28 DAY)");
$stats['avg_visits_per_week'] = round($result->fetch_assoc()['count'] / 4, 1);

//recent activity
$result = $conn->query("SELECT * FROM ACTIVITY_LOG ORDER BY timestamp DESC LIMIT 15"); //limiting to 15 activites
$recent_activities = $result->fetch_all(MYSQLI_ASSOC);

//logins from last seven days
$result = $conn->query("SELECT COUNT(*) as count FROM ACTIVITY_LOG WHERE action_type = 'LOGIN' AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['recent_logins'] = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        
        .admin-header p {
            margin: 10px 0 0 0;
            opacity: 0.8;
            color: #555;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--surface-color);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
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
        
        .nav-button.secondary:hover {
            background: #4b5563;
        }
        
        .nav-button.danger {
            background: #b91c1c;
        }
        
        .nav-button.danger:hover {
            background: #991b1b;
        }
        
        .recent-activity {
            background: var(--surface-color);
            padding: 22px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.4);
        }
        
        .recent-activity h3 {
            margin-top: 0;
            color: var(--text-primary);
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .activity-description {
            margin: 5px 0;
        }
        
        .activity-user {
            color: #667eea;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Administrator Dashboard</h1>
        </div>
        
        <div class="admin-nav">
            <a href="users.php" class="nav-button">Manage Users</a>
            <a href="activity.php" class="nav-button">Activity Logs</a>
            <a href="reports.php" class="nav-button">System Reports</a>
            <a href="../logout.php" class="nav-button danger">Logout</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_patients']; ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_doctors']; ?></div>
                <div class="stat-label">Total Doctors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_visits']; ?></div>
                <div class="stat-label">Total Visits</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['visits_this_week']; ?></div>
                <div class="stat-label">Visits This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['avg_visits_per_week']; ?></div>
                <div class="stat-label">Avg Visits/Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['recent_logins']; ?></div>
                <div class="stat-label">Recent Logins (7 days)</div>
            </div>
        </div>
        
        <div class="recent-activity">
            <h3>Recent System Activity</h3>
            <?php if (empty($recent_activities)): ?>
                <p>No recent activity to display.</p>
            <?php else: ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-description">
                            <span class="activity-user"><?php echo htmlspecialchars($activity['user_role']); ?></span>
                            <?php echo htmlspecialchars($activity['action_description']); ?>
                        </div>
                        <div class="activity-time">
                            <?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
