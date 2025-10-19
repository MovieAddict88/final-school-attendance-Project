<?php
// Simple CSRF protection helpers
// Requires session_start() to have been called before using these functions.

if (session_status() !== PHP_SESSION_ACTIVE) {
    // We do not call session_start() here to avoid conflicts with calling code ordering.
}

/**
 * Get or generate the CSRF token for the current session.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Echo a hidden input field containing the CSRF token.
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST body (form) or X-CSRF-Token header for JSON requests.
 * If invalid, terminate the request with 403.
 */
function verify_csrf_or_die(): void {
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    $token = $postToken ?: $headerToken;

    if (!$sessionToken || !$token || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
}
