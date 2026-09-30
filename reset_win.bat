@echo off
setlocal EnableDelayedExpansion
:: Dừng mọi tiến trình Ảnh Cưới còn treo (PHP cổng 8686-8688 + cửa sổ tunnel). Không xóa dữ liệu.
IF EXIST "%~dp0router.php" (SET WEB_ROOT=%~dp0) ELSE (FOR %%I IN ("%~dp0..") DO SET WEB_ROOT=%%~fI\Web\)

FOR /F "tokens=5" %%i IN ('netstat -ano ^| findstr ":8686 " ^| findstr "LISTENING"') DO TASKKILL /F /PID %%i >nul 2>&1
FOR /F "tokens=5" %%i IN ('netstat -ano ^| findstr ":8687 " ^| findstr "LISTENING"') DO TASKKILL /F /PID %%i >nul 2>&1
FOR /F "tokens=5" %%i IN ('netstat -ano ^| findstr ":8688 " ^| findstr "LISTENING"') DO TASKKILL /F /PID %%i >nul 2>&1
DEL /Q "!WEB_ROOT!cloudflared\beat.run" >nul 2>&1
TASKKILL /F /T /FI "WINDOWTITLE eq AnhCuoi Beat*" >nul 2>&1
TASKKILL /F /T /FI "WINDOWTITLE eq AnhCuoi Tunnel*" >nul 2>&1
TASKKILL /F /T /FI "WINDOWTITLE eq AnhCuoi URL*" >nul 2>&1
DEL /Q "!WEB_ROOT!database\.public_url" >nul 2>&1
echo Xong. Chay lai run_window.bat
pause
