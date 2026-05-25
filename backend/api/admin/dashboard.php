<?php
/**
 * GET /api/admin/dashboard
 * 管理员仪表盘统计数据
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../middleware/AdminMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('仅支持 GET 请求', 405);
}

requireAdmin();
$db = getDB();

// 基本统计
$totalUsers    = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalChallenges = (int)$db->query("SELECT COUNT(*) FROM challenges")->fetchColumn();
$activeChallenges = (int)$db->query("SELECT COUNT(*) FROM challenges WHERE is_active = 1")->fetchColumn();
$totalSubmissions = (int)$db->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
$correctSubmissions = (int)$db->query("SELECT COUNT(*) FROM submissions WHERE is_correct = 1")->fetchColumn();
$totalTeams     = (int)$db->query("SELECT COUNT(*) FROM teams")->fetchColumn();

// 今日数据
$todaySubmissions = (int)$db->query(
    "SELECT COUNT(*) FROM submissions WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

$todayRegistrations = (int)$db->query(
    "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

$todaySolves = (int)$db->query(
    "SELECT COUNT(*) FROM solves WHERE DATE(solved_at) = CURDATE()"
)->fetchColumn();

// 各分类题数
$stmt = $db->query(
    "SELECT cat.name, COUNT(c.id) AS count
     FROM categories cat
     LEFT JOIN challenges c ON cat.id = c.category_id
     GROUP BY cat.id, cat.name
     ORDER BY cat.sort_order"
);
$categoryStats = $stmt->fetchAll();

// 最近提交
$stmt = $db->query(
    "SELECT s.id, s.is_correct, s.created_at,
            u.username, c.title AS challenge_title
     FROM submissions s
     JOIN users u ON s.user_id = u.id
     JOIN challenges c ON s.challenge_id = c.id
     ORDER BY s.created_at DESC
     LIMIT 10"
);
$recentSubmissions = $stmt->fetchAll();

// 排行榜 TOP 10
$stmt = $db->query(
    "SELECT username, score FROM users WHERE is_active = 1 ORDER BY score DESC LIMIT 10"
);
$topUsers = $stmt->fetchAll();

jsonSuccess([
    'stats' => [
        'total_users'         => $totalUsers,
        'total_challenges'    => $totalChallenges,
        'active_challenges'   => $activeChallenges,
        'total_submissions'   => $totalSubmissions,
        'correct_submissions' => $correctSubmissions,
        'accuracy_rate'       => $totalSubmissions > 0 ? round($correctSubmissions / $totalSubmissions * 100, 1) : 0,
        'total_teams'         => $totalTeams,
    ],
    'today' => [
        'submissions'   => $todaySubmissions,
        'registrations' => $todayRegistrations,
        'solves'        => $todaySolves,
    ],
    'category_stats'      => $categoryStats,
    'recent_submissions'  => $recentSubmissions,
    'top_users'           => $topUsers,
]);
