<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
const RAE_ADMIN_PASSWORD_HASH = '$2y$12$z4v1hbbVZHf22t4MJpig9ejgj5eB9W.KDOEtixbKXTxPGI./KzaCu'; // Temporary password: 8888
const RAE_ADMIN_SESSION_KEY = 'rae_admin_authenticated';
function rae_admin_is_authenticated(): bool { return !empty($_SESSION[RAE_ADMIN_SESSION_KEY]); }
function rae_admin_login(string $password): bool {
    if (password_verify($password, RAE_ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION[RAE_ADMIN_SESSION_KEY] = true;
        $_SESSION['rae_admin_login_time'] = date('c');
        return true;
    }
    return false;
}
function rae_admin_logout(): void { unset($_SESSION[RAE_ADMIN_SESSION_KEY], $_SESSION['rae_admin_login_time']); }
function rae_admin_require_auth(): void {
    if (!rae_admin_is_authenticated()) {
        http_response_code(403);
        exit('Admin authorization required.');
    }
}
function rae_audit_actor(): string { return 'RAE-ADMIN'; }
?>
