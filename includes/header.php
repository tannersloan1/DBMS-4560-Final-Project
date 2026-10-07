<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
?>
<div class="lp-nav">
  <div class="lp-nav__inner">
    <div class="brand">Healthcare Portal</div>
    <div class="lp-links">
      <a href="/healthcare/index.php">Home</a>
      <?php if ($isLoggedIn): ?>
        <?php if ($role === 'doctor'): ?>
          <a href="/healthcare/doctor/view_patient.php">Dashboard</a>
        <?php elseif ($role === 'admin'): ?>
          <a href="/healthcare/admin/dashboard.php">Dashboard</a>
        <?php else: ?>
          <a href="/healthcare/patient/view_records.php">Dashboard</a>
        <?php endif; ?>
        <a href="/healthcare/logout.php">Logout</a>
      <?php else: ?>
        <a href="/healthcare/login.php">Login</a>
        <a href="/healthcare/register.php">Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</div>