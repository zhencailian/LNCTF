@echo off
chcp 65001 >nul
title LNCTF 前端构建工具
color 0B

echo ============================================
echo   LNCTF 前端构建工具
echo   构建 Vue 3 前端为生产版本
echo ============================================
echo.

:: 切换到 frontend 目录
cd /d "%~dp0frontend"

if not exist "node_modules" (
    echo [1/2] 安装依赖...
    call npm install
    if %errorlevel% neq 0 (
        echo   ✗ 安装失败，请检查 npm 是否可用
        pause
        exit /b 1
    )
    echo   ✓ 依赖安装完成
) else (
    echo [1/2] 依赖已安装，跳过
)

echo [2/2] 构建前端...
call npm run build
if %errorlevel% neq 0 (
    echo   ✗ 构建失败！
    pause
    exit /b 1
)

echo   ✓ 构建成功！
echo.
echo   构建输出目录: "%~dp0frontend\dist\"
echo.
echo   下一步：重启 Apache（在 PhpStudy 中操作）
echo   然后访问 http://localhost:8080/
echo.
pause
