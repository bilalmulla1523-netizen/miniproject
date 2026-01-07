<?php 
session_start();
require 'config.php'; // PDO connection

// --------------------- SIMPLE LOGIN SYSTEM ---------------------
$login_error = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        $user = trim($_POST['username']);
        $pass = trim($_POST['password']);

        // CHANGE THESE
        $ADMIN_USER = 'admin';
        $ADMIN_PASS = 'admin123';

        if ($user === $ADMIN_USER && $pass === $ADMIN_PASS) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "Invalid username or password.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
    body {
        font-family: system-ui; background:#f3f4f6; 
        display:flex; justify-content:center; align-items:center; height:100vh;
    }
    .card {
        background:#fff; padding:25px; border-radius:10px;
        box-shadow:0 10px 25px rgba(15,23,42,0.15); width:300px;
    }
    input { width:100%; padding:10px; margin-top:8px; border-radius:6px; border:1px solid #ccc; }
    button { width:100%; padding:10px; margin-top:12px; background:#2563eb; color:white; border:none; border-radius:6px; }
</style>
</head>
<body>
<div class="card">
<h2>Admin Login</h2>
<form method="post">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login</button>
<p style="color:red;"><?= $login_error ?></p>
</form>
</div>
</body>
</html>
<?php
exit;
}
// --------------------- END LOGIN ---------------------


// --------------------- FILTERS & VIEW ---------------------
$view = $_GET['view'] ?? 'checks';
$search = trim($_GET['search'] ?? '');
$suspicious = $_GET['suspicious'] ?? 'all';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalRows = 0;
$rows = [];

if ($view === 'reports') {
    $where = "FROM site_reports WHERE 1=1";
    $params = [];

    // Searching
    if ($search !== '') {
        $where .= " AND url LIKE :search";
        $params[':search'] = "%".$search."%";
    }

    if ($from_date) {
        $where .= " AND DATE(reported_at) >= :from_date";
        $params[':from_date'] = $from_date;
    }
    if ($to_date) {
        $where .= " AND DATE(reported_at) <= :to_date";
        $params[':to_date'] = $to_date;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) $where");
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * $where ORDER BY reported_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // --------- CHECKS TABLE ----------
    $where = "FROM site_checks WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $where .= " AND url LIKE :search";
        $params[':search'] = "%".$search."%";
    }

    if ($from_date) {
        $where .= " AND DATE(checked_at) >= :from_date";
        $params[':from_date'] = $from_date;
    }
    if ($to_date) {
        $where .= " AND DATE(checked_at) <= :to_date";
        $params[':to_date'] = $to_date;
    }

    if ($suspicious === 'only') $where .= " AND is_suspicious = 1";
    if ($suspicious === 'clean') $where .= " AND is_suspicious = 0";

    $stmt = $pdo->prepare("SELECT COUNT(*) $where");
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * $where ORDER BY checked_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalPages = max(1, ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Panel</title>
<style>
    body { font-family: system-ui; background:#f3f4f6; margin:0; }
    .topbar { background:#1f2937; color:white; padding:14px 20px; display:flex; justify-content:space-between; }
    .wrapper { padding:20px; max-width:1100px; margin:auto; }
    table { width:100%; border-collapse:collapse; margin-top:10px; font-size:14px; }
    th,td { padding:10px; border-bottom:1px solid #ddd; }
    th { background:#e5e7eb; }
    select, input[type=date], input[type=text] { padding:6px; }
    button { padding:6px 10px; background:#2563eb; border:none; color:white; border-radius:4px; cursor:pointer; }
</style>
</head>
<body>

<div class="topbar">
    <h2>Admin Panel – URL Checker</h2>
    <a href="admin.php?logout=1" style="color:white;">Logout</a>
</div>

<div class="wrapper">

<!-- Navigation Tabs -->
<a href="admin.php?view=checks" style="margin-right:10px; <?= $view=='checks'?'font-weight:bold':'' ?>">URL Checks</a>
<a href="admin.php?view=reports" style="<?= $view=='reports'?'font-weight:bold':'' ?>">Reports</a>

<!-- Filters -->
<form method="get" style="margin-top:20px;">
<input type="hidden" name="view" value="<?= $view ?>">

Search:
<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="example.com">

From:
<input type="date" name="from_date" value="<?= $from_date ?>">

To:
<input type="date" name="to_date" value="<?= $to_date ?>">

<?php if ($view === 'checks'): ?>
Suspicious:
<select name="suspicious">
    <option value="all" <?= $suspicious=='all'?'selected':'' ?>>All</option>
    <option value="only" <?= $suspicious=='only'?'selected':'' ?>>Only Suspicious</option>
    <option value="clean" <?= $suspicious=='clean'?'selected':'' ?>>Only Clean</option>
</select>
<?php endif; ?>

<button type="submit">Apply</button>
</form>

<!-- Table -->
<table>
<?php if ($view === 'checks'): ?>
<tr>
    <th>ID</th>
    <th>URL</th>
    <th>Suspicious?</th>
    <th>Suspension</th>
    <th>Checked At</th>
    <th>IP</th>
    <th>User Agent</th>
    <th>Action</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['url']) ?></td>
    <td><?= $r['is_suspicious'] ? 'Yes' : 'No' ?></td>

    <!-- Suspension Column -->
    <td>
        <form method="POST" action="update_status.php">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <select name="suspension_status">
                <option value="Active" <?= $r['suspension_status']=="Active"?"selected":"" ?>>Active</option>
                <option value="Suspended" <?= $r['suspension_status']=="Suspended"?"selected":"" ?>>Suspended</option>
                <option value="Under Review" <?= $r['suspension_status']=="Under Review"?"selected":"" ?>>Under Review</option>
            </select>
    </td>

    <td><?= $r['checked_at'] ?></td>
    <td><?= $r['ip_address'] ?></td>
    <td><?= htmlspecialchars($r['user_agent']) ?></td>

    <td><button type="submit">Update</button></form></td>
</tr>
<?php endforeach; ?>

<?php else: ?>
<tr>
    <th>ID</th>
    <th>URL</th>
    <th>Reason</th>
    <th>Reported At</th>
    <th>IP</th>
    <th>User Agent</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= $r['id'] ?></td>
    <td><?= htmlspecialchars($r['url']) ?></td>
    <td><?= nl2br(htmlspecialchars($r['reason'])) ?></td>
    <td><?= $r['reported_at'] ?></td>
    <td><?= $r['ip_address'] ?></td>
    <td><?= htmlspecialchars($r['user_agent']) ?></td>
</tr>
<?php endforeach; ?>

<?php endif; ?>
</table>

</div>
</body>
</html>
