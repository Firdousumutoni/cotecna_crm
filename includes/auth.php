<?php
// includes/auth.php
// Central Authentication Guard & Role-Based Access Control (RBAC)
// Include this file AFTER session_start() has been called.

/**
 * All valid roles in the system.
 */
define('VALID_ROLES', ['admin', 'manager', 'voc', 'hr', 'finance', 'it', 'sales&marketing', 'staff']);

/**
 * Role → Allowed pages map.
 * 'receipt' is an internal sub-page of invoices, always bundled with invoices.
 */
$ROLE_PERMISSIONS = [
    'admin'          => ['dashboard', 'clients', 'inspections', 'interactions', 'deals', 'invoices', 'receipt', 'reports', 'settings', 'notifications', 'certificates'],
    'manager'        => ['dashboard', 'clients', 'inspections', 'interactions', 'deals', 'invoices', 'receipt', 'reports', 'notifications', 'certificates'],
    'voc'            => ['dashboard', 'clients', 'inspections', 'interactions', 'deals', 'invoices', 'receipt', 'reports', 'notifications', 'certificates'],
    'hr'             => ['dashboard', 'clients', 'inspections', 'interactions', 'deals', 'invoices', 'receipt', 'reports', 'notifications', 'certificates'],
    'sales&marketing'=> ['dashboard', 'clients', 'inspections', 'interactions', 'deals', 'invoices', 'receipt', 'reports', 'notifications', 'certificates'],
    'it'             => ['dashboard', 'clients', 'inspections', 'interactions', 'deals', 'invoices', 'receipt', 'reports', 'notifications', 'certificates'],
    'staff'          => ['dashboard', 'clients', 'inspections', 'interactions', 'deals', 'invoices', 'receipt', 'reports', 'notifications', 'certificates'],
    'finance'        => ['dashboard', 'invoices', 'receipt', 'reports', 'certificates', 'notifications'],
];

/**
 * Redirect to login if no session exists.
 */
function require_auth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Check if the current session user can access a given page.
 */
function can_access_page($page) {
    global $ROLE_PERMISSIONS;
    $role    = $_SESSION['user_role'] ?? 'staff';
    $allowed = $ROLE_PERMISSIONS[$role] ?? [];
    return in_array($page, $allowed);
}

/**
 * Deny access with a styled 403 page.
 */
function deny_access() {
    $role     = $_SESSION['user_role'] ?? 'unknown';
    $roleLabel = strtoupper($role);
    http_response_code(403);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied — Cotecna CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.12);border:1px solid #e2e8f0;padding:50px 40px;text-align:center;max-width:440px;width:90%;}
        .icon-wrap{width:80px;height:80px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;}
        .icon-wrap i{font-size:2rem;color:#ef4444;}
        h2{font-size:1.5rem;font-weight:700;color:#1e293b;margin-bottom:12px;}
        p{font-size:0.95rem;color:#64748b;line-height:1.65;}
        .role-badge{display:inline-block;margin:16px 0;padding:4px 14px;border-radius:20px;background:#fef3c7;color:#92400e;font-size:0.8rem;font-weight:700;letter-spacing:0.5px;}
        .btn{display:inline-flex;align-items:center;gap:8px;margin-top:24px;padding:12px 28px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:white;border-radius:10px;text-decoration:none;font-weight:600;font-size:0.9rem;transition:transform 0.2s,box-shadow 0.2s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(59,130,246,0.35);}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap"><i class="fa-solid fa-lock"></i></div>
        <h2>Access Denied</h2>
        <p>You don't have permission to view this page.</p>
        <div class="role-badge">ROLE: {$roleLabel}</div>
        <p>Contact your system administrator if you believe this is a mistake.</p>
        <a href="index.php?page=dashboard" class="btn">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
HTML;
    exit;
}

/**
 * Validate and sanitize a text input.
 * Returns trimmed string or throws on failure.
 */
function validate_text($value, $fieldName, $maxLen = 255, $required = true) {
    $value = trim($value);
    if ($required && $value === '') {
        throw new InvalidArgumentException("{$fieldName} is required and cannot be empty.");
    }
    if (strlen($value) > $maxLen) {
        throw new InvalidArgumentException("{$fieldName} must not exceed {$maxLen} characters.");
    }
    return $value;
}

/**
 * Validate an email address.
 */
function validate_email($value, $fieldName = 'Email') {
    $value = trim($value);
    if ($value === '') {
        throw new InvalidArgumentException("{$fieldName} is required.");
    }
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("{$fieldName} must be a valid email address (e.g. user@example.com).");
    }
    if (strlen($value) > 150) {
        throw new InvalidArgumentException("{$fieldName} must not exceed 150 characters.");
    }
    return $value;
}

/**
 * Validate a numeric amount/price field.
 */
function validate_number($value, $fieldName, $min = 0, $required = true) {
    $value = trim($value);
    if ($required && $value === '') {
        throw new InvalidArgumentException("{$fieldName} is required.");
    }
    if (!is_numeric($value)) {
        throw new InvalidArgumentException("{$fieldName} must be a valid number (digits only).");
    }
    if ((float)$value < $min) {
        throw new InvalidArgumentException("{$fieldName} must be at least {$min}.");
    }
    return $value;
}

/**
 * Validate a phone number.
 * Accepts formats: +254712345678 / 0712345678 / digits, spaces, dashes.
 */
function validate_phone($value, $fieldName = 'Phone Number', $required = false) {
    $value = trim($value);
    if ($value === '' && !$required) return $value;
    if ($value === '' && $required) {
        throw new InvalidArgumentException("{$fieldName} is required.");
    }
    // Allow optional +, then digits, spaces, dashes. Length 7–20.
    if (!preg_match('/^\+?[\d\s\-]{7,20}$/', $value)) {
        throw new InvalidArgumentException("{$fieldName} must be a valid phone number (e.g. +254712345678 or 0712-345-678).");
    }
    return $value;
}

/**
 * Validate that a value is in a whitelist.
 */
function validate_whitelist($value, array $allowed, $fieldName) {
    if (!in_array($value, $allowed, true)) {
        throw new InvalidArgumentException("Invalid {$fieldName} selected. Please choose a valid option.");
    }
    return $value;
}

/**
 * Validate a date string (YYYY-MM-DD).
 */
function validate_date($value, $fieldName, $required = true) {
    $value = trim($value);
    if ($required && $value === '') {
        throw new InvalidArgumentException("{$fieldName} is required.");
    }
    if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        throw new InvalidArgumentException("{$fieldName} must be a valid date in YYYY-MM-DD format.");
    }
    return $value;
}

/**
 * Validate a password to enforce strict complexity rules.
 * Requires: min 8 chars, at least 1 uppercase, 1 lowercase, 1 number, and 1 special character.
 */
function validate_password($password) {
    if (empty($password)) {
        throw new InvalidArgumentException("Password is required.");
    }
    if (strlen($password) < 8) {
        throw new InvalidArgumentException("Password must be at least 8 characters long.");
    }
    if (!preg_match('/[A-Z]/', $password)) {
        throw new InvalidArgumentException("Password must contain at least one uppercase letter.");
    }
    if (!preg_match('/[a-z]/', $password)) {
        throw new InvalidArgumentException("Password must contain at least one lowercase letter.");
    }
    if (!preg_match('/[0-9]/', $password)) {
        throw new InvalidArgumentException("Password must contain at least one number.");
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        throw new InvalidArgumentException("Password must contain at least one special character (e.g. !@#$%^&*).");
    }
    return $password;
}
