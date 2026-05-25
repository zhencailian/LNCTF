<?php
/**
 * LNCTF Auth 调试接口
 * GET /api/debug/auth
 *
 * 用于验证 Authorization 头是否正确传递到 PHP。
 * 访问方式：
 *   1. 浏览器直接打开 http://lnctf.local:8080/api/debug/auth（不带 Token，可看到无 Token 时的诊断信息）
 *   2. F12 控制台执行：fetch('/api/debug/auth', {headers:{'Authorization':'Bearer '+localStorage.getItem('token')}}).then(r=>r.json()).then(console.log)
 */

header('Content-Type: application/json; charset=utf-8');

// 收集所有可能的 Authorization 信息来源
$sources = [
    '$_SERVER[\'HTTP_AUTHORIZATION\']'           => $_SERVER['HTTP_AUTHORIZATION'] ?? '(未设置)',
    '$_SERVER[\'REDIRECT_HTTP_AUTHORIZATION\']'  => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '(未设置)',
    '$_SERVER[\'HTTP_X_AUTHORIZATION\']'         => $_SERVER['HTTP_X_AUTHORIZATION'] ?? '(未设置)',
    '$_SERVER[\'Authorization\']'                => $_SERVER['Authorization'] ?? '(未设置)',
];

// getallheaders() 在 FastCGI 模式下可能不可用
$allHeaders = function_exists('getallheaders') ? getallheaders() : false;

// 尝试用 getBearerToken 解析
$bearerToken = null;
if (function_exists('getBearerToken')) {
    $bearerToken = getBearerToken();
}

$result = [
    'status' => 'ok',
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '(未设置)',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? '(未设置)',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '(未设置)',
    'auth_header_sources' => $sources,
    'getallheaders_available' => $allHeaders !== false,
    'getallheaders_authorization' => $allHeaders ? ($allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? '(未找到)') : '(N/A)',
    'getBearerToken_result' => $bearerToken ? substr($bearerToken, 0, 30) . '...' : ($bearerToken === null ? '(null - 未获取到 Token)' : '(空)'),
    'has_token_in_header' => $bearerToken !== null,
    'diagnosis' => [
        '✅ CGIPassAuth 生效' => isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']),
        '✅ REDIRECT 后备生效' => isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']),
        '✅ getallheaders 后备可用' => $allHeaders !== false && !empty($allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? ''),
        '✅ getBearerToken 成功' => $bearerToken !== null,
    ],
    'fix_suggestions' => [
        '如果 HTTP_AUTHORIZATION 为 "(未设置)"' => '检查 vhost 配置是否包含 CGIPassAuth On 且已重启 Apache',
        '如果仅在 REDIRECT_HTTP_AUTHORIZATION 中' => 'Apache 内部重写时添加了 REDIRECT_ 前缀，目前已有后备处理逻辑',
        '如果 getallheaders 不可用' => 'PHP 运行在 FastCGI 模式下，这正常。此时依赖 HTTP_AUTHORIZATION SERVER 变量',
    ],
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
