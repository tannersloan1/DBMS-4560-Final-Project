<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Healthcare Portal – Welcome</title>
  <link rel="stylesheet" href="css/styles.css?v=5"/>
</head>
<body>


<!-- NAV -->
<?php include 'includes/header.php'; ?>

<?php
$dashboard_link =
    ($role === 'doctor') ? '/healthcare/doctor/view_patient.php' :
    (($role === 'admin') ? '/healthcare/admin/dashboard.php' :
    '/healthcare/patient/view_records.php');
?>

<div class="hero-wrap">
  <div class="hero">
    <p>Securely access visit summaries, manage profile details, and (for doctors) review patient records, all in one streamlined portal.</p>
    <div class="cta-row">
      <?php if (!$isLoggedIn): ?>
        <a href="login.php">Login</a>
        <a href="register.php">Create Account</a>
      <?php else: ?>
        <a href="<?= $dashboard_link ?>">Go to Dashboard</a>
      <?php endif; ?>
    </div>
  </div>
</div>


  <!-- FEATURES -->
  <section class="section">
    <div class="kicker">Why choose us</div>
    <h2 class="h2">Fast, secure, and easy to use</h2>
    <div class="grid">
      <div class="card">
        <h3>Secure by default</h3>
        <p>Follows best practices; use hashed passwords and role-based access. Sessions keep you safely signed in.</p>
      </div>
      <div class="card">
        <h3>Patient-first design</h3>
        <p>Simple dashboards that surface what matters: visits, contacts, and health history.</p>
      </div>
      <div class="card">
        <h3>Doctor tools</h3>
        <p>Quickly view patients, add visit notes, and generate reports to share.</p>
      </div>
      <div class="card">
        <h3>Anywhere access</h3>
        <p>Runs locally on XAMPP and can be deployed to any PHP host later.</p>
      </div>
    </div>
  </section>


  <!-- GALLERY-->
  <section class="section">
    <div class="kicker">A quick look</div>
    <h2 class="h2">Clean, modern, accessible</h2>
    <div class="gallery">
      <img alt="Clinic hallway" src="https://img-new.cgtrader.com/items/1889561/c9ec1a266b/hospital-hallway-3-max-3d-model-c9ec1a266b.webp" />
      <img alt="Doctor workstation" src="https://images.unsplash.com/photo-1586773860418-d37222d8fce3?q=80&w=1200&auto=format&fit=crop" />
      <img alt="Tablet with records" src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?q=80&w=1200&auto=format&fit=crop" />
      <img alt="Medical team" src="https://www.99mgmt.com/hs-fs/hubfs/iStock-998313080.jpg?width=2121&name=iStock-998313080.jpg" />
    </div>
  </section>

  <section class="section">
    <div class="quote">
      "I can find what I need in seconds. The new portal saves us time every day." — Dr. Daniels
    </div>
    <div style="margin-top:16px">
      <?php if (!$isLoggedIn): ?>
        <a class="btn" href="/healthcare/register.php">Create your account</a>
        <a class="btn" href="/healthcare/login.php" style="margin-left:8px;background:#0f3a57;">I already have an account</a>
      <?php else: ?>
        
        <a class="btn" href="<?= $dashboard_link ?>">
          Open my dashboard
        </a>
      <?php endif; ?>
    </div>
  </section>


  <div class="lp-footer">© <?= date('Y') ?> Healthcare Portal</div>
</body>
</html>
