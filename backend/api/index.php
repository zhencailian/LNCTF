<?php
/**
 * LNCTF API 入口 & 路由
 *
 * 统一入口，解析 REQUEST_URI 分发到对应处理器
 *
 * URL 格式: /api/{module}/{action}[/{id}]
 * 例如:    /api/auth/login
 *          /api/challenges/list
 *          /api/challenges/detail/5
 */

// 全局异常捕获，确保任何未处理异常都返回 JSON
set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'code'    => 500,
        'message' => '服务器内部错误: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cors.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/auth.php';

// ==================== 路由解析 ====================
$uri = $_SERVER['REQUEST_URI'];
// 移除查询字符串
$uri = strtok($uri, '?');
// 移除 base path（如果部署在子目录）
$uri = preg_replace('#^/api#', '', $uri);
$uri = trim($uri, '/');
$segments = $uri ? explode('/', $uri) : [];

$module = $segments[0] ?? 'index';
$action = $segments[1] ?? 'index';
$param  = $segments[2] ?? null;

// ==================== 路由表 ====================
$routes = [
    // ---- Auth ----
    'auth' => [
        'login'    => __DIR__ . '/auth/login.php',
        'register' => __DIR__ . '/auth/register.php',
        'logout'   => __DIR__ . '/auth/logout.php',
        'me'       => __DIR__ . '/auth/me.php',
    ],
    // ---- Challenges ----
    'challenges' => [
        'list'       => __DIR__ . '/challenges/list.php',
        'detail'     => __DIR__ . '/challenges/detail.php',
        'categories' => __DIR__ . '/challenges/categories.php',
    ],
    // ---- Submissions ----
    'submissions' => [
        'submit'  => __DIR__ . '/submissions/submit.php',
        'list'    => __DIR__ . '/submissions/list.php',
    ],
    // ---- Leaderboard ----
    'leaderboard' => [
        'index' => __DIR__ . '/leaderboard/index.php',
        'teams' => __DIR__ . '/leaderboard/teams.php',
    ],
    // ---- Teams ----
    'teams' => [
        'create'  => __DIR__ . '/teams/create.php',
        'join'    => __DIR__ . '/teams/join.php',
        'leave'   => __DIR__ . '/teams/leave.php',
        'dismiss' => __DIR__ . '/teams/dismiss.php',
        'info'    => __DIR__ . '/teams/info.php',
        'my'      => __DIR__ . '/teams/my.php',
    ],
    // ---- Notices ----
    'notices' => [
        'list' => __DIR__ . '/notices/list.php',
    ],
    // ---- Announcements ----
    'announcements' => [
        'list' => __DIR__ . '/announcements/list.php',
    ],
    // ---- Writeups ----
    'writeups' => [
        'create' => __DIR__ . '/writeups/create.php',
        'list'   => __DIR__ . '/writeups/list.php',
    ],
    // ---- Profile ----
    'profile' => [
        'index'  => __DIR__ . '/profile/index.php',
        'update' => __DIR__ . '/profile/update.php',
        'solves' => __DIR__ . '/profile/solves.php',
    ],
    // ---- Admin ----
    'admin' => [
        'dashboard'    => __DIR__ . '/admin/dashboard.php',
        'challenges'   => __DIR__ . '/admin/challenges.php',
        'categories'   => __DIR__ . '/admin/categories.php',
        'users'        => __DIR__ . '/admin/users.php',
        'notices'      => __DIR__ . '/admin/notices.php',
        'submissions'  => __DIR__ . '/admin/submissions.php',
    ],
    // ---- Debug（仅开发环境） ----
    'debug' => [
        'auth' => __DIR__ . '/debug/auth.php',
    ],
];

// ==================== 分发 ====================
if (isset($routes[$module][$action])) {
    $handler = $routes[$module][$action];
    // 传入 URL 参数
    $GLOBALS['route_param'] = $param;
    require $handler;
} else {
    // 默认路由：显示 API 信息
    if ($module === 'index' || ($module === '' && $action === 'index')) {
        jsonSuccess([
            'name'    => 'LNCTF API',
            'version' => '2.0',
            'status'  => 'running',
        ]);
    } else {
        jsonError("路由不存在: /api/$module/$action", 404);
    }
}
