<?php
/**
 * Manage Students Directory (Frontend)
 * Campus Complaint & Maintenance Management System
 */
require_once __DIR__ . '/../../backend/config/db.php';
require_once __DIR__ . '/../../backend/includes/auth.php';
require_once __DIR__ . '/../../backend/includes/functions.php';

requireLogin('admin');

// Fetch all buildings for select dropdowns
try {
    $buildings = getBuildings($pdo);
} catch (Exception $e) {
    $buildings = [];
}

// Fetch all students for directory view
try {
    $stmt = $pdo->query(
        "SELECT s.*, u.name, u.email, u.phone, u.status, b.building_name 
         FROM students s
         JOIN users u ON s.user_id = u.user_id
         LEFT JOIN buildings b ON s.building_id = b.building_id
         ORDER BY u.name ASC"
    );
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    $students = [];
}

$pageTitle = "Manage Students";
$currentPage = "students";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card mb-lg stagger-in">
    <div class="card-header">
        <h3><i class="fas fa-user-graduate text-gradient"></i> Student Directory</h3>
        <button onclick="openAddModal()" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus"></i> Add New Student
        </button>
    </div>
    
    <div class="card-body">
        <!-- Live Table Filter -->
        <div class="table-filters">
            <div class="filter-search input-group" style="max-width: 400px;">
                <input type="text" id="student-search" class="form-control" placeholder="Search by name, ID, or department...">
                <i class="fas fa-search input-group-icon"></i>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table" id="students-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Hostel / Building</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="table-empty">
                                <i class="fas fa-users-slash"></i>
                                <p>No student records logged in system database catalog.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong class="text-primary"><?= sanitize($student['student_number']) ?></strong></td>
                                <td><?= sanitize($student['name']) ?></td>
                                <td><?= sanitize($student['email']) ?></td>
                                <td><?= sanitize($student['department'] ?: '—') ?> (<?= sanitize($student['semester'] ?: '—') ?>)</td>
                                <td><?= sanitize($student['building_name'] ?: '—') ?> <?= !empty($student['room_no']) ? "(Rm " . sanitize($student['room_no']) . ")" : "" ?></td>
                                <td>
                                    <span class="badge <?= $student['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst(sanitize($student['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="table-actions" style="justify-content: flex-end;">
                                        <button onclick='openEditModal(<?= json_encode($student) ?>)' class="btn btn-outline btn-sm btn-icon" title="Edit Student">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="handleDelete(<?= $student['user_id'] ?>)" class="btn btn-outline btn-sm btn-icon text-danger" title="Delete Student">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── ADD/EDIT STUDENT MODAL ─── -->
<div class="modal-overlay" id="student-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modal-title">Add Student Record</h3>
            <button class="modal-close" onclick="Modal.close('student-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="student-form" onsubmit="saveStudent(event)">
            <input type="hidden" name="user_id" id="m-user-id">
            <input type="hidden" name="action" id="m-action" value="add">
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="m-name" class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="name" id="m-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="m-email" class="form-label">Email Address <span class="required">*</span></label>
                        <input type="email" name="email" id="m-email" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="m-phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="m-phone" class="form-control">
                    </div>
                    <div class="form-group" id="password-group">
                        <label for="m-password" class="form-label">Password <span class="required">*</span></label>
                        <input type="password" name="password" id="m-password" class="form-control" placeholder="••••••••">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="m-student-number" class="form-label">Student ID / Roll <span class="required">*</span></label>
                        <input type="text" name="student_number" id="m-student-number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="m-department" class="form-label">Department</label>
                        <input type="text" name="department" id="m-department" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="m-semester" class="form-label">Semester</label>
                        <input type="text" name="semester" id="m-semester" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="m-building-id" class="form-label">Hostel / Location</label>
                        <select name="building_id" id="m-building-id" class="form-control">
                            <option value="">-- None --</option>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?= $b['building_id'] ?>"><?= sanitize($b['building_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="m-room-no" class="form-label">Room Number</label>
                        <input type="text" name="room_no" id="m-room-no" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="m-status" class="form-label">Account Status</label>
                        <select name="status" id="m-status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="Modal.close('student-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modal-submit-btn">Save Student</button>
            </div>
        </form>
    </div>
</div>

<?php 
$extraScripts = "
<script>
setupTableSearch('student-search', 'students-table');

function openAddModal() {
    document.getElementById('student-form').reset();
    document.getElementById('m-action').value = 'add';
    document.getElementById('m-user-id').value = '';
    document.getElementById('modal-title').textContent = 'Add Student Record';
    document.getElementById('m-password').setAttribute('required', 'required');
    document.getElementById('password-group').style.display = 'block';
    Modal.open('student-modal');
}

function openEditModal(student) {
    document.getElementById('m-action').value = 'edit';
    document.getElementById('m-user-id').value = student.user_id;
    document.getElementById('modal-title').textContent = 'Modify Student Record';
    
    document.getElementById('m-name').value = student.name;
    document.getElementById('m-email').value = student.email;
    document.getElementById('m-phone').value = student.phone || '';
    document.getElementById('m-student-number').value = student.student_number;
    document.getElementById('m-department').value = student.department || '';
    document.getElementById('m-semester').value = student.semester || '';
    document.getElementById('m-building-id').value = student.building_id || '';
    document.getElementById('m-room-no').value = student.room_no || '';
    document.getElementById('m-status').value = student.status;
    
    // Hide or make password optional during edits
    document.getElementById('m-password').removeAttribute('required');
    document.getElementById('m-password').placeholder = 'Leave blank to keep current';
    
    Modal.open('student-modal');
}

async function saveStudent(e) {
    e.preventDefault();
    if (!validateForm('student-form')) return;
    
    const formData = new FormData(document.getElementById('student-form'));
    const res = await ajaxRequest('" . BACKEND_URL . "/admin/ajax/student_crud.php', 'POST', formData);
    if (res.success) {
        Toast.success('Done', res.message);
        Modal.close('student-modal');
        setTimeout(() => location.reload(), 1000);
    } else {
        Toast.error('Failure', res.message);
    }
}

function handleDelete(id) {
    confirmAction(
        'Delete Student Record', 
        'Are you sure you want to permanently erase this student directory record? All filed complaints from this student will also be affected.',
        async () => {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('user_id', id);
            
            const res = await ajaxRequest('" . BACKEND_URL . "/admin/ajax/student_crud.php', 'POST', formData);
            if (res.success) {
                Toast.success('Done', res.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                Toast.error('Failure', res.message);
            }
        }
    );
}
</script>
";
require_once __DIR__ . '/../includes/footer.php'; 
?>
