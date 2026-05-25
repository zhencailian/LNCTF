@echo off
chcp 65001 >nul
title LNCTF 一键部署工具
color 0B

echo ============================================
echo   LNCTF 训练平台 · 一键部署工具
echo ============================================
echo.
echo 当前目录: %CD%
echo.

:: ---- 步骤 1：检查项目文件 ----
echo [1/4] 检查项目文件完整性...
set MISSING=0
if not exist "backend\config\database.php" (echo   ✗ 缺少 database.php & set /a MISSING+=1)
if not exist "backend\api\index.php" (echo   ✗ 缺少 index.php & set /a MISSING+=1)
if not exist "database\schema.sql" (echo   ✗ 缺少 schema.sql & set /a MISSING+=1)
if not exist "frontend\index.html" (echo   ✗ 缺少 frontend\index.html & set /a MISSING+=1)
if %MISSING% gtr 0 (
    echo.
    echo   有 %MISSING% 个文件缺失，请检查项目结构！
    pause
    exit /b 1
)
echo   ✓ 所有核心文件已就绪
echo.

:: ---- 步骤 2：检查 php ----
echo [2/4] 检查 PHP 环境...
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo   ⚠ 未找到 PHP（可能未加入环境变量）
    echo     确保 PhpStudy 的 Apache 和 MySQL 已启动即可
) else (
    php -v | findstr "^PHP" >nul
    if %errorlevel% equ 0 (
        for /f "tokens=2" %%i in ('php -v ^| findstr "^PHP"') do echo   ✓ PHP 版本: %%i
    )
)
echo.

:: ---- 步骤 3：数据库导入提示 ----
echo [3/4] 数据库设置
echo.
echo   数据库 SQL 文件位置: %CD%\database\schema.sql
echo   默认连接配置：127.0.0.1:3307 / lnctf / root / root
echo.
echo   操作步骤：
echo   1. 打开浏览器访问 http://localhost/phpmyadmin
echo   2. 新建数据库 lnctf（字符集 utf8mb4）
echo   3. 点击顶部 "SQL" 选项卡
echo   4. 将 database\schema.sql 的全部内容粘贴进去
echo   5. 点击 "执行"
echo.
echo   或者用命令行导入：
echo   mysql -h 127.0.0.1 -P 3307 -u root -p lnctf ^< database\schema.sql
echo.

:: ---- 步骤 4：PhpStudy 配置提示 ----
echo [4/4] PhpStudy 配置
echo.
echo   ■ 打开 PhpStudy → 网站 → 创建网站：
echo   ┌─────────────────────────────────────────────┐
echo   │ 域名：   localhost                          │
echo   │ 端口：   8080（推荐；如改成 80 需同步前端代理）│
echo   │ 根目录： %CD%                               │
echo   └─────────────────────────────────────────────┘
echo.
echo   ■ 检查 Apache 的 rewrite_mod 已启用
echo     PhpStudy → Apache → 配置文件 → httpd.conf
echo     搜索 #LoadModule rewrite_module 去掉前面的 #
echo     搜索 AllowOverride 把 None 改为 All
echo.
echo   ■ 重启 Apache
echo.
echo   ■ 验证后端：浏览器访问 http://localhost:8080/api/index
echo     看到 {"code":200,"data":{"name":"LNCTF API","version":"2.0","status":"running"}} 即成功
echo.
echo   ■ 验证前端：浏览器访问 http://localhost:8080/
echo     应能正常打开前端页面
echo.
echo ============================================
echo   部署准备完成！
echo ============================================
pause
