<?php
/**
 * POST /api/submissions/submit
 * 提交 Flag
 * 限流：每分钟最多 10 次
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('仅支持 POST 请求', 405);
}

$payload = requireAuth();
$userId = (int)$payload['sub'];

$challengeId = (int)input('challenge_id');
$flag        = input('flag');

if (!$challengeId || !$flag) {
    jsonError('缺少题目 ID 或 Flag', 422);
}

$db = getDB();

if ($db->inTransaction()) {
    $db->rollBack();
}

// ---- 限流检查：一分钟内提交次数 ----
$stmt = $db->prepare(
    "SELECT COUNT(*) FROM submissions
     WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
);
$stmt->execute([$userId]);
$recentCount = (int)$stmt->fetchColumn();
if ($recentCount >= 10) {
    jsonError('提交过于频繁，请 1 分钟后再试', 429);
}

// ---- 检查题目是否存在且活跃 ----
$stmt = $db->prepare("SELECT id, title, flag, score, solve_count, category_id FROM challenges WHERE id = ? AND is_active = 1");
$stmt->execute([$challengeId]);
$challenge = $stmt->fetch();

if (!$challenge) {
    logActivity($userId, 'submit_invalid', 'challenge', $challengeId, ['flag' => substr($flag, 0, 20) . '...']);
    jsonError('题目不存在或未发布', 404);
}

// ---- 检查是否已经解过 ----
$stmt = $db->prepare("SELECT id FROM solves WHERE user_id = ? AND challenge_id = ? LIMIT 1");
$stmt->execute([$userId, $challengeId]);
if ($stmt->fetch()) {
    jsonError('你已经解过这道题了', 409);
}

// ---- 校验 Flag ----
$isCorrect = password_verify($flag, $challenge['flag']);

// ---- 记录提交 ----
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$stmt = $db->prepare(
    "INSERT INTO submissions (user_id, challenge_id, submitted_flag, is_correct, ip_address, solved_at, created_at)
     VALUES (?, ?, ?, ?, ?, IF(? = 1, NOW(), NULL), NOW())"
);
$stmt->execute([$userId, $challengeId, $flag, $isCorrect ? 1 : 0, $ip, $isCorrect ? 1 : 0]);
$submissionId = (int)$db->lastInsertId();

// ---- Flag 错误 ----
if (!$isCorrect) {
    logActivity($userId, 'submit_wrong', 'challenge', $challengeId);
    jsonError('Flag 错误，请重试', 400);
}

// ---- Flag 正确：记录解题 ----
try {
    $db->beginTransaction();
    // 计算 solve_order
    $stmt = $db->prepare("SELECT COUNT(*) FROM solves WHERE challenge_id = ?");
    $stmt->execute([$challengeId]);
    $solveOrder = (int)$stmt->fetchColumn() + 1;

    // 动态计分
    $scoreEarned = dynamicScore((int)$challenge['score'], $solveOrder - 1);

    // 一血加成（第一个解出 +20%）
    if ($solveOrder === 1) {
        $scoreEarned = (int)round($scoreEarned * 1.2);
    }

    // 写入 solves
    $stmt = $db->prepare(
        "INSERT INTO solves (user_id, challenge_id, score_earned, solve_order, solved_at)
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$userId, $challengeId, $scoreEarned, $solveOrder]);

    // 更新用户总分
    $stmt = $db->prepare("UPDATE users SET score = (SELECT COALESCE(SUM(score_earned), 0) FROM solves WHERE user_id = ?) WHERE id = ?");
    $stmt->execute([$userId, $userId]);

    // 队伍总分更新：如果用户有队伍，重新计算该队伍所有成员总分之和
    $stmt = $db->prepare("SELECT team_id FROM users WHERE id = ? AND team_id IS NOT NULL");
    $stmt->execute([$userId]);
    $teamRow = $stmt->fetch();
    if ($teamRow) {
        $teamId = (int)$teamRow['team_id'];
        $stmt = $db->prepare("UPDATE teams t SET t.score = (SELECT COALESCE(SUM(u.score), 0) FROM users u WHERE u.team_id = t.id) WHERE t.id = ?");
        $stmt->execute([$teamId]);
    }

    // 更新题目的 solve_count
    $stmt = $db->prepare("UPDATE challenges SET solve_count = (SELECT COUNT(*) FROM solves WHERE challenge_id = ?) WHERE id = ?");
    $stmt->execute([$challengeId, $challengeId]);

    // 一血处理
    if ($solveOrder === 1) {
        $stmt = $db->prepare("UPDATE challenges SET first_blood_id = ? WHERE id = ? AND first_blood_id IS NULL");
        $stmt->execute([$userId, $challengeId]);
    }

    // 更新 submission 的 solved_at
    $stmt = $db->prepare("UPDATE submissions SET solved_at = NOW() WHERE id = ?");
    $stmt->execute([$submissionId]);

    $totalScoreStmt = $db->prepare("SELECT score FROM users WHERE id = ?");
    $totalScoreStmt->execute([$userId]);
    $totalScore = (int)$totalScoreStmt->fetchColumn();

    $db->commit();

    logActivity($userId, 'submit_correct', 'challenge', $challengeId, [
        'score' => $scoreEarned,
        'order' => $solveOrder,
    ]);

    jsonSuccess([
        'score_earned' => $scoreEarned,
        'solve_order'  => $solveOrder,
        'is_first_blood' => $solveOrder === 1,
        'total_score'  => $totalScore,
    ], '恭喜！Flag 正确！');

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonError('解题记录失败: ' . $e->getMessage(), 500);
}
