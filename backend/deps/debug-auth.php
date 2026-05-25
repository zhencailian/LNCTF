<?php
/**
 * LNCTF Auth 调试工具
 * 访问:  http://lnctf.local:8080/api/debug-auth
 * 或:    http://localhost:8080/api/debug-auth
 *
 * 在浏览器中打开后，F12 → 控制台执行下面的 JS 发送带 Token 的请求:
 *   fetch('/api/debug-auth', {
 *     headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
 *   }).then(r => r.json()).then(console.log)
 *
 * 或在浏览器地址栏直接访问（无 Authorization 头）:
 *   http://lnctf.local:8080/api/debug-auth
 */

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'SERVER 变量' => [
        'HTTP_AUTHORIZATION'           => $_SERVER['HTTP_AUTHORIZATION'] ?? '(未设置)',
        'REDIRECT_HTTP_AUTHORIZATION'  => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '(未设置)',
        'HTTP_HOST'                    => $_SERVER['HTTP_HOST'] ?? '(未设置)',
        'REQUEST_URI'                  => $_SERVER['REQUEST_URI'] ?? '(未设置)',
        'SERVER_SOFTWARE'              => $_SERVER['SERVER_SOFTWARE'] ?? '(未设置)',
        'PHP_VERSION'                  => PHP_VERSION,
    ],
    'getallheaders()' => function_exists('getallheaders') ? getallheaders() : '(函数不存在)',
    'apache_request_headers()' => function_exists('apache_request_headers') ? apache_request_headers() : '(函数不存在)',
    'Authorization 头解析结果' => function_exists('getBearerToken') ? (getBearerToken() ?? '(null - 未获取到 Token)') : '(getBearerToken 函数未定义)',
    '诊断说明' => [
        '正常情况' => 'HTTP_AUTHORIZATION 应显示 "Bearer xxx..."',
        'CGIPassAuth 生效' => 'CGIPassAuth On 开启后，HTTP_AUTHORIZATION 应显示正确的 Bearer Token',
        'REDIRECT_前缀' => '如果只显示在 REDIRECT_HTTP_AUTHORIZATION 而不在 HTTP_AUTHORIZATION，说明 Apache 重写时添加了 REDIRECT_ 前缀',
        'getallheaders 不存在' => 'PHP 运行在 CGI/FastCGI 模式下时 getallheaders() 不可用，这正常',
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
