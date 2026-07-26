<?php
/**
 * Admin Dashboard
 * Campus Complaint & Maintenance Management System
 */
require_once __DIR__ . '/../../backend/config/db.php';
require_once __DIR__ . '/../../backend/includes/auth.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

requireLogin('admin');

try {
    // 1. Core Metrics
    $totalComplaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    
    $statusPendingId = getStatusIdByName($pdo, 'Pending') ?: 1;
    $pendingComplaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status_id = $statusPendingId")->fetchColumn();
    
    $inProgressCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM complaints c
         JOIN complaint_status cs ON c.status_id = cs.status_id
         WHERE cs.status_name IN ('Assigned', 'Accepted', 'In Progress')"
    )->fetchColumn();
    
    $statusResolvedId = getStatusIdByName($pdo, 'Resolved') ?: 5;
    $resolvedComplaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status_id = $statusResolvedId")->fetchColumn();

    // 2. Fetch Category distribution
    $categoryData = $pdo->query(
        "SELECT cc.category_name, COUNT(c.complaint_id) as count 
         FROM complaint_categories cc
         LEFT JOIN complaints c ON cc.category_id = c.category_id
         GROUP BY cc.category_id 
         ORDER BY count DESC"
    )->fetchAll();

    // 3. Fetch Building distribution
    $buildingData = $pdo->query(
        "SELECT b.building_name, COUNT(c.complaint_id) as count 
         FROM buildings b
         LEFT JOIN complaints c ON b.building_id = c.building_id
         GROUP BY b.building_id 
         ORDER BY count DESC"
    )->fetchAll();

    // 4. Staff Performance Index
    $staffPerf = $pdo->query(
        "SELECT u.name, ms.employee_id, ms.specialization,
                COUNT(CASE WHEN a.assignment_status = 'Completed' THEN 1 END) as completed_tasks,
                ROUND(AVG(f.rating), 1) as avg_rating
         FROM maintenance_staff ms
         JOIN users u ON ms.user_id = u.user_id
         LEFT JOIN assignments a ON ms.staff_id = a.staff_id
         LEFT JOIN feedback f ON a.complaint_id = f.complaint_id
         WHERE u.status = 'active'
         GROUP BY ms.staff_id
         ORDER BY completed_tasks DESC, avg_rating DESC
         LIMIT 5"
    )->fetchAll();

    // 5. Recent Complaints
    $recentComplaints = $pdo->query(
        "SELECT c.*, cs.status_name, cc.category_name, b.building_name, u.name as student_name
         FROM complaints c
         JOIN complaint_status cs ON c.status_id = cs.status_id
         JOIN complaint_categories cc ON c.category_id = cc.category_id
         JOIN buildings b ON c.building_id = b.building_id
         JOIN students s ON c.student_id = s.student_id
         JOIN users u ON s.user_id = u.user_id
         ORDER BY c.created_at DESC
         LIMIT 6"
    )->fetchAll();

} catch (Exception $e) {
    $totalComplaints = $pendingComplaints = $inProgressCount = $resolvedComplaints = 0;
    $categoryData = $buildingData = $staffPerf = $recentComplaints = [];
}

// Convert PHP arrays to JSON for ChartJS
$chartCategories = [];
$chartCategoryCounts = [];
foreach ($categoryData as $cat) {
    $chartCategories[] = $cat['category_name'];
    $chartCategoryCounts[] = (int)$cat['count'];
}

$chartBuildings = [];
$chartBuildingCounts = [];
foreach ($buildingData as $bld) {
    $chartBuildings[] = $bld['building_name'];
    $chartBuildingCounts[] = (int)$bld['count'];
}

$pageTitle = "Admin Dashboard";
$currentPage = "dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ─── Admin Metrics Cards ─── -->
<div class="stats-grid stagger-in">
    <div class="stat-card stat-primary">
        <div class="stat-card-top">
            <div class="stat-label">Total Logged</div>
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
        </div>
        <div class="stat-value" data-count="<?= $totalComplaints ?>">0</div>
        <div class="stat-change">Total tickets in system</div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-card-top">
            <div class="stat-label">Pending Reviews</div>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
        <div class="stat-value" data-count="<?= $pendingComplaints ?>">0</div>
        <div class="stat-change">Awaiting assignment</div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-card-top">
            <div class="stat-label">Active Repairs</div>
            <div class="stat-icon"><i class="fas fa-spinner"></i></div>
        </div>
        <div class="stat-value" data-count="<?= $inProgressCount ?>">0</div>
        <div class="stat-change">In progress by technicians</div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-card-top">
            <div class="stat-label">Resolved Tickets</div>
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="stat-value" data-count="<?= $resolvedComplaints ?>">0</div>
        <div class="stat-change">Successfully closed</div>
    </div>
</div>

<!-- ─── Charts Display Section ─── -->
<div class="grid-2 stagger-in mt-lg" style="grid-template-columns: 1fr 1fr;">
    <!-- Chart 1: Category Distribution -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie text-gradient"></i> By Complaint Category</h3>
        </div>
        <div class="card-body">
            <div style="height: 260px; position: relative;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart 2: Building Distribution -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar text-gradient"></i> By Campus Location</h3>
        </div>
        <div class="card-body">
            <div style="height: 260px; position: relative;">
                <canvas id="buildingChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ─── Recent Complaints & Quick Links ─── -->
<div class="grid-3 stagger-in mt-lg" style="grid-template-columns: 2fr 1fr;">
    <!-- Recent Complaints -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history text-gradient"></i> Recent Complaint Submissions</h3>
            <a href="complaints.php" class="btn btn-outline btn-sm">View All Complaints</a>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Student</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentComplaints)): ?>
                            <tr>
                                <td colspan="5" class="table-empty">
                                    <i class="fas fa-inbox"></i>
                                    <p>No complaints submitted yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentComplaints as $rc): ?>
                                <tr>
                                    <td><strong class="text-primary">#CMP-<?= str_pad($rc['complaint_id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td><?= sanitize($rc['student_name']) ?></td>
                                    <td><?= sanitize($rc['title']) ?></td>
                                    <td><?= sanitize($rc['category_name']) ?></td>
                                    <td><?= getStatusBadge($rc['status_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Directory Quick Controls -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-th-large text-gradient"></i> Quick Controls</h3>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; gap: 12px;">
            <a href="manage_students.php" class="btn btn-outline w-full" style="justify-content: flex-start;">
                <i class="fas fa-user-graduate text-primary"></i> Manage Students
            </a>
            <a href="manage_staff.php" class="btn btn-outline w-full" style="justify-content: flex-start;">
                <i class="fas fa-hard-hat text-primary"></i> Manage Technicians
            </a>
            <a href="manage_categories.php" class="btn btn-outline w-full" style="justify-content: flex-start;">
                <i class="fas fa-tags text-primary"></i> Manage Categories
            </a>
            <a href="manage_locations.php" class="btn btn-outline w-full" style="justify-content: flex-start;">
                <i class="fas fa-map-marker-alt text-primary"></i> Manage Locations
            </a>
            <a href="reports.php" class="btn btn-primary w-full" style="justify-content: flex-start; margin-top: 8px;">
                <i class="fas fa-chart-bar"></i> Analytics & Reports
            </a>
        </div>
    </div>
</div>

<?php 
$extraScripts = "
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Category Chart
    const ctxCat = document.getElementById('categoryChart')?.getContext('2d');
    if (ctxCat) {
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: " . json_encode($chartCategories) . ",
                datasets: [{
                    data: " . json_encode($chartCategoryCounts) . ",
                    backgroundColor: [
                        '#4f46e5', '#7c3aed', '#0891b2', '#2563eb', '#db2777', 
                        '#059669', '#d97706', '#dc2626', '#14b8a6', '#f43f5e'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { family: 'Inter', size: 12 } }
                    }
                }
            }
        });
    }

    // 2. Building Chart
    const ctxBld = document.getElementById('buildingChart')?.getContext('2d');
    if (ctxBld) {
        new Chart(ctxBld, {
            type: 'bar',
            data: {
                labels: " . json_encode($chartBuildings) . ",
                datasets: [{
                    label: 'Complaints',
                    data: " . json_encode($chartBuildingCounts) . ",
                    backgroundColor: 'rgba(79, 70, 229, 0.85)',
                    hoverBackgroundColor: '#4f46e5',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { font: { family: 'Inter' } } },
                    y: { beginAtZero: true, ticks: { font: { family: 'Inter' }, precision: 0 } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
";
require_once __DIR__ . '/../includes/footer.php'; 
?>
