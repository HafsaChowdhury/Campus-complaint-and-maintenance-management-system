<?php
/**
 * Authentication & Session Management (Backend)
 * Campus Complaint Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login — redirect to login page if not authenticated.
 * Optionally restrict to a specific role.
 */
function requireLogin($role = null) {
    if (!isLoggedIn()) {
        $loginUrl = defined('FRONTEND_URL') ? FRONTEND_URL . '/login.php' : '../login.php';
        header('Location: ' . $loginUrl);
        exit();
    }
    if ($role !== null && $_SESSION['role'] !== $role) {
        redirectToDashboard();
        exit();
    }
}

/**
 * Redirect user to their role-specific dashboard
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        $loginUrl = defined('FRONTEND_URL') ? FRONTEND_URL . '/login.php' : '../login.php';
        header('Location: ' . $loginUrl);
        exit();
    }
    $baseUrl = defined('FRONTEND_URL') ? FRONTEND_URL : '../frontend';
    $role = $_SESSION['role'] ?? 'student';
    switch ($role) {
        case 'admin':
            header('Location: ' . $baseUrl . '/admin/dashboard.php');
            break;
        case 'staff':
            header('Location: ' . $baseUrl . '/staff/dashboard.php');
            break;
        default:
            header('Location: ' . $baseUrl . '/student/dashboard.php');
            break;
    }
    exit();
}

/**
 * Get current user data from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id'    => $_SESSION['user_id'],
        'name'       => $_SESSION['name'],
        'email'      => $_SESSION['email'],
        'role'       => $_SESSION['role'],
        'student_id' => $_SESSION['student_id'] ?? null,
        'staff_id'   => $_SESSION['staff_id'] ?? null,
    ];
}

/**
 * Store user data in session after successful login
 */
function setUserSession($user) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
}

/**
 * Get count of unread notifications for the current user
 */
function getUnreadNotificationCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get recent notifications for a user
 */
function getNotifications($pdo, $userId, $limit = 10) {
    $limit = (int)$limit;
    $stmt = $pdo->prepare(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Mark a notification as read
 */
function markNotificationRead($pdo, $notificationId, $userId) {
    $stmt = $pdo->prepare(
        "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?"
    );
    $stmt->execute([$notificationId, $userId]);
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsRead($pdo, $userId) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
}
