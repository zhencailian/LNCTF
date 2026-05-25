<?php
/**
 * GET /api/profile/index
 * 获取用户资料（支持查看他人）
 *
 * Query 参数:
 *   id - 用户ID（可选，不传返回当前用户）
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

$db = getDB();
$payload = requireAuth();
$userId = (int)(getQuery('id') ?: $payload['sub']);

$stmt = $db->prepare(
    "SELECT u.id, u.username, u.email, u.avatar_url, u.score, u.role, u.created_at,
            u.team_id, t.name AS team_name
     FROM users u
     LEFT JOIN teams t ON u.team_id = t.id
     WHERE u.id = ?"
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    jsonError('用户不存在', 404);
}

// 解题统计
$stmt = $db->prepare(
    "SELECT COUNT(*) AS total_solved,
            SUM(score_earned) AS total_score
     FROM solves WHERE user_id = ?"
);
$stmt->execute([$userId]);
$stats = $stmt->fetch();

// 按分类统计解题
$stmt = $db->prepare(
    "SELECT cat.name AS category, COUNT(*) AS count
     FROM solves s
     JOIN challenges c ON s.challenge_id = c.id
     JOIN categories cat ON c.category_id = cat.id
     WHERE s.user_id = ?
     GROUP BY cat.name
     ORDER BY count DESC"
);
$stmt->execute([$userId]);
$categoryStats = $stmt->fetchAll();

// 最近解题
$stmt = $db->prepare(
    "SELECT c.id, c.title, c.difficulty, c.score, s.score_earned, s.solved_at
     FROM solves s
     JOIN challenges c ON s.challenge_id = c.id
     WHERE s.user_id = ?
     ORDER BY s.solved_at DESC
     LIMIT 10"
);
$stmt->execute([$userId]);
$recentSolves = $stmt->fetchAll();

foreach ($recentSolves as &$s) {
    $s['id'] = (int)$s['id'];
    $s['score'] = (int)$s['score'];
    $s['score_earned'] = (int)$s['score_earned'];
}
unset($s);

$isOwner = (int)$payload['sub'] === $userId;

jsonSuccess([
    'id'             => (int)$user['id'],
    'username'       => $user['username'],
    'email'          => $isOwner ? $user['email'] : null,
    'avatar_url'     => $user['avatar_url'],
    'role'           => $user['role'],
    'team_id'        => $user['team_id'] ? (int)$user['team_id'] : null,
    'team_name'      => $user['team_name'],
    'score'          => (int)$stats['total_score'],
    'total_solved'   => (int)$stats['total_solved'],
    'category_stats' => $categoryStats,
    'recent_solves'  => $recentSolves,
    'created_at'     => $user['created_at'],
    'is_owner'       => $isOwner,
]);
