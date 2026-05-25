<?php
/**
 * LNCTF CORS 跨域处理
 *
 * 支持多个来源域名，自动回显请求来源
 */

// 允许的来源列表
$allowedOrigins = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
    'http://lnctf.local:8080',
    'http://lnctf.local:3000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 如果请求来源在允许列表中，回显具体 Origin（不能使用 * 配合 Credentials）
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // 兜底：允许无来源请求（如 Postman、curl、同源请求）
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// 预检请求直接返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
