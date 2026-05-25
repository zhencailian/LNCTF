<?php
/**
 * 管理员鉴权中间件
 * 在需要管理员权限的 API 入口引入
 */

require_once __DIR__ . '/../includes/auth.php';

function requireAdmin(): array {
    $payload = requireAuth();
    if ($payload['role'] !== 'admin') {
        jsonError('权限不足，需要管理员权限', 403);
    }
    return $payload;
}
