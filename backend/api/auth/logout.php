<?php
/**
 * POST /api/auth/logout
 * 用户注销（由前端清除 Token）
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('仅支持 POST 请求', 405);
}

$payload = requireAuth();
logActivity($payload['sub'], 'logout', 'user', $payload['sub']);

jsonSuccess(null, '已退出登录');
