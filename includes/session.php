<?php
// Session configuration and helpers for improved security.

// Only set session configuration if not already started
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // Use secure session cookies with HttpOnly and SameSite options
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Name session explicitly to avoid default name collisions
    session_name('sms_session');

    session_start();
}

/**
 * Regenerate session ID after privilege change (e.g., login) to prevent fixation.
 */
function session_regenerate(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
