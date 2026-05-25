<?php
/**
 * GET /api/leaderboard/index
 * 个人排行榜
 *
 * Query 参数:
 *   page     - 页码
 *   per_page - 每页条数
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$db = getDB();

$page    = max(1, (int)getQuery('page', 1));
$perPage = min(100, max(1, (int)getQuery('per_page', 50)));
$offset  = ($page - 1) * $perPage;

// 总数
$total = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();

// 排行榜（按分数降序，按最后解题时间升序打破平局）
$stmt = $db->prepare(
    "SELECT u.id, u.username, u.avatar_url, u.score,
            (SELECT COUNT(*) FROM solves WHERE user_id = u.id) AS solved_count,
            (SELECT MAX(solved_at) FROM solves WHERE user_id = u.id) AS last_solve_at
     FROM users u
     WHERE u.is_active = 1
     ORDER BY u.score DESC, last_solve_at ASC
     LIMIT ? OFFSET ?"
);
$stmt->execute([$perPage, $offset]);
$users = $stmt->fetchAll();

$rank = $offset + 1;
foreach ($users as &$user) {
    $user['id'] = (int)$user['id'];
    $user['score'] = (int)$user['score'];
    $user['solved_count'] = (int)$user['solved_count'];
    $user['rank'] = $rank++;
}
unset($user);

jsonPaginated($users, $total, $page, $perPage);
