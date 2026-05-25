<?php
/**
 * LNCTF JWT 简易认证（无需 Composer 依赖）
 * 使用 HMAC-SHA256 自实现 JWT
 */

/**
 * Base64 URL 安全编码
 */
function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL 安全解码
 */
function base64UrlDecode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

/**
 * 生成 JWT Token
 */
function generateToken(int $userId, string $role): string {
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode([
        'sub'   => $userId,
        'role'  => $role,
        'iat'   => time(),
        'exp'   => time() + JWT_EXPIRY,
    ]));
    $signature = base64UrlEncode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );
    return "$header.$payload.$signature";
}

/**
 * 验证 JWT Token
 * @return array|null 返回 payload 或 null（无效）
 */
function verifyToken(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $payload, $signature] = $parts;

    // 验证签名
    $expectedSig = base64UrlEncode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );
    if (!hash_equals($expectedSig, $signature)) return null;

    // 解码 Payload
    $data = json_decode(base64UrlDecode($payload), true);
    if (!$data || !isset($data['exp'])) return null;

    // 检查过期
    if ($data['exp'] < time()) return null;

    return $data;
}

/**
 * 从请求头中提取 Token
 */
function getBearerToken(): ?string {
    $headers = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $ah = apache_request_headers();
        $headers = $ah['Authorization'] ?? $ah['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s+(.*)$/i', $headers, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * 获取当前认证用户（返回 payload 或中断请求）
 */
function requireAuth(): array {
    $token = getBearerToken();
    if (!$token) {
        jsonError('未登录，请先登录', 401);
    }
    $payload = verifyToken($token);
    if (!$payload) {
        jsonError('Token 无效或已过期，请重新登录', 401);
    }
    return $payload;
}

/**
 * 获取当前用户（不中断请求，未登录返回 null）
 */
function getCurrentUser(): ?array {
    $token = getBearerToken();
    if (!$token) return null;
    return verifyToken($token);
}
