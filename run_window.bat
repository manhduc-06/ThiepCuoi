@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul
title Anh Cuoi
color 0F

:: Chạy Ảnh Cưới trên Windows: php\php.exe (đóng gói sẵn).
:: Link công khai (https://xxxx.jagame.vn) do WEB tự quản — script chỉ chạy PHP, tải cloudflared lần
:: đầu và gõ /health mỗi phút để web tự xin link / bật lại tunnel.

SET SCRIPT_DIR=%~dp0

:: ── Bố cục: RELEASE (phẳng) hay DEV (Web\ + Tools\ tách) ─────────────────
IF EXIST "%SCRIPT_DIR%router.php" (
    SET WEB_ROOT=%SCRIPT_DIR%
    SET PHP_DIR=%SCRIPT_DIR%php
    SET CF_TOOL_DIR=%SCRIPT_DIR%cloudflared
) ELSE (
    FOR %%I IN ("%SCRIPT_DIR%..") DO SET ROOT_DIR=%%~fI
    SET WEB_ROOT=!ROOT_DIR!\Web\
    SET PHP_DIR=!ROOT_DIR!\Tools\php
    SET CF_TOOL_DIR=!ROOT_DIR!\Tools\cloudflared
)
SET PHP_EXE=!PHP_DIR!\php.exe
SET CF_BIN=!CF_TOOL_DIR!\cloudflared.exe
SET CF_CONF_DIR=!WEB_ROOT!cloudflared
SET DB_DIR=!WEB_ROOT!database

:: Tiến trình nền tự gọi lại chính file này (xem cuối file).
IF "%~1"=="__beat" GOTO beat

IF NOT EXIST "!PHP_EXE!" (
    echo [LOI] Khong tim thay php.exe: !PHP_EXE!
    pause
    exit /b 1
)
IF NOT EXIST "!CF_CONF_DIR!" mkdir "!CF_CONF_DIR!"
IF NOT EXIST "!DB_DIR!\sessions" mkdir "!DB_DIR!\sessions"
IF NOT EXIST "!WEB_ROOT!uploads\photos" mkdir "!WEB_ROOT!uploads\photos"

:: ── Chọn cổng: 8686 bận thì lùi 8687/8688 (mỗi dòng một lệnh, không khối lồng — chạy đúng cả khi file LF)
SET PORT=
SET PBUSY=
FOR /F "delims=" %%i IN ('netstat -ano ^| findstr ":8686 " ^| findstr "LISTENING"') DO SET PBUSY=1
IF NOT DEFINED PBUSY SET PORT=8686
SET PBUSY=
IF NOT DEFINED PORT FOR /F "delims=" %%i IN ('netstat -ano ^| findstr ":8687 " ^| findstr "LISTENING"') DO SET PBUSY=1
IF NOT DEFINED PORT IF NOT DEFINED PBUSY SET PORT=8687
SET PBUSY=
IF NOT DEFINED PORT FOR /F "delims=" %%i IN ('netstat -ano ^| findstr ":8688 " ^| findstr "LISTENING"') DO SET PBUSY=1
IF NOT DEFINED PORT IF NOT DEFINED PBUSY SET PORT=8688
IF NOT DEFINED PORT echo [LOI] Ca 3 cong 8686, 8687, 8688 deu ban. Dong bot cua so Anh Cuoi cu roi mo lai.
IF NOT DEFINED PORT pause
IF NOT DEFINED PORT exit /b 1
>"!DB_DIR!\.app_port" <nul set /p =!PORT!
DEL /Q "!DB_DIR!\.public_url" >nul 2>&1

:: ── cloudflared.exe (web tự bật tunnel; ở đây chỉ bảo đảm có chương trình) ───
IF NOT EXIST "!CF_BIN!" (
    echo   Dang tai cloudflared.exe ^(lan dau^)...
    powershell -NoProfile -Command "try { Invoke-WebRequest -Uri 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe' -OutFile '!CF_BIN!' -UseBasicParsing } catch { Write-Host 'Tai that bai' }"
)

echo ========================================
echo   Anh Cuoi
echo   May nay : http://localhost:!PORT!   ^(quan tri: /admin^)
echo   Internet: link rieng xxxx.jagame.vn tu tao sau khi cai dat xong
echo   Dong cua so nay de dung
echo ========================================
echo.

:: Nhịp gõ /health mỗi phút (cửa sổ thu nhỏ riêng, tự thoát khi file cờ bị xóa).
echo running> "!CF_CONF_DIR!\beat.run"
START "AnhCuoi Beat" /MIN cmd /c ""%~f0" __beat "!PORT!""

cd /d "!WEB_ROOT!"
SET PHP_CLI_SERVER_WORKERS=4
"!PHP_EXE!" -c "!PHP_DIR!\php.ini" -S localhost:!PORT! router.php

:: ── PHP thoát: dừng nhịp gõ, tắt tunnel do web bật (cửa sổ tiêu đề "AnhCuoi Tunnel") ─
DEL /Q "!CF_CONF_DIR!\beat.run" >nul 2>&1
TASKKILL /F /T /FI "WINDOWTITLE eq AnhCuoi Beat*" >nul 2>&1
TASKKILL /F /T /FI "WINDOWTITLE eq AnhCuoi Tunnel*" >nul 2>&1
DEL /Q "!CF_CONF_DIR!\web_tunnel.pid" "!DB_DIR!\.public_url" >nul 2>&1
pause
EXIT /B 0

:: ── Nhịp gõ: web tự xin link xxxx.jagame.vn / bật lại tunnel khi cần ─────
:beat
SET BEAT_PORT=%~2
timeout /t 3 /nobreak >nul
:beat_loop
IF NOT EXIST "!CF_CONF_DIR!\beat.run" EXIT /B 0
curl.exe -s -o nul --max-time 60 http://localhost:!BEAT_PORT!/health >nul 2>&1
IF ERRORLEVEL 1 powershell -NoProfile -Command "try { Invoke-WebRequest -UseBasicParsing -TimeoutSec 60 'http://localhost:!BEAT_PORT!/health' | Out-Null } catch {}"
timeout /t 60 /nobreak >nul
GOTO beat_loop
