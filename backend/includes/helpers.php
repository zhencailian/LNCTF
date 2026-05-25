<?php
/**
 * LNCTF 通用工具函数
 */

/**
 * 获取 JSON 请求体
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * 获取查询参数
 */
function getQuery(string $key, $default = null): ?string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/**
 * 安全获取 POST/JSON 字段
 */
function input(string $key, $default = null): ?string {
    // 先检查 JSON Body
    static $jsonBody = null;
    if ($jsonBody === null) {
        $jsonBody = getJsonBody();
    }
    if (isset($jsonBody[$key])) {
        $v = $jsonBody[$key];
        return is_string($v) ? trim($v) : $v;
    }
    // 再检查 POST
    if (isset($_POST[$key])) {
        return trim($_POST[$key]);
    }
    // 最后 GET
    if (isset($_GET[$key])) {
        return trim($_GET[$key]);
    }
    return $default;
}

/**
 * 生成随机字符串
 */
function randomStr(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 记录操作日志
 */
function logActivity(int $userId = null, string $action, string $targetType = null, int $targetId = null, array $detail = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO activity_log (user_id, action, target_type, target_id, detail, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $userId,
            $action,
            $targetType,
            $targetId,
            $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (Exception $e) {
        // 日志失败不影响主流程
    }
}

/**
 * 动态计分算法
 * score = baseScore × (0.8^(solveCount / decayFactor))
 */
function dynamicScore(int $baseScore, int $solveCount, int $decayFactor = 5): int {
    if ($solveCount === 0) return $baseScore;
    $score = $baseScore * pow(0.8, $solveCount / $decayFactor);
    return max(round($score), 1);
}
