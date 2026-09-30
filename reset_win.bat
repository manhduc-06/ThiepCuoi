@echo off
setlocal EnableDelayedExpansion
:: Dừng tiến trình Ảnh Cưới CỦA THƯ MỤC CÀI ĐẶT NÀY còn treo (PHP cổng 8686-8688 + tunnel). Không xóa dữ liệu.
:: Chỉ tắt php.exe / cloudflared.exe nằm trong thư mục cài đặt này (hoặc cloudflared chạy với cấu hình
:: anhcuoi-tunnel.yml của thư mục này): chương trình khác dùng cổng 8686 không bị đụng tới.
IF EXIST "%~dp0router.php" (
    SET WEB_ROOT=%~dp0
    SET AC_ROOT=%~dp0
) ELSE (
    FOR %%I IN ("%~dp0..") DO SET AC_ROOT=%%~fI\
    SET WEB_ROOT=!AC_ROOT!Web\
)
SET AC_CONF=!WEB_ROOT!cloudflared\anhcuoi-tunnel.yml

FOR %%P IN (8686 8687 8688) DO (
    FOR /F "tokens=5" %%i IN ('netstat -ano ^| findstr ":%%P " ^| findstr "LISTENING"') DO CALL :killmine %%i %%P
)
DEL /Q "!WEB_ROOT!cloudflared\beat.run" >nul 2>&1
:: cloudflared của bản cài này (web bật trong cửa sổ "AnhCuoi Tunnel"): nhận ra qua đường dẫn cấu hình riêng.
powershell -NoProfile -Command "Get-CimInstance Win32_Process -Filter \"Name='cloudflared.exe'\" | Where-Object { $_.CommandLine -and $_.CommandLine.IndexOf($env:AC_CONF, [StringComparison]::OrdinalIgnoreCase) -ge 0 } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force; Write-Host ('Da tat link cong khai (PID ' + $_.ProcessId + ').') }"
DEL /Q "!WEB_ROOT!database\.public_url" "!WEB_ROOT!cloudflared\web_tunnel.pid" >nul 2>&1
echo Xong. Chay lai run_window.bat
pause
EXIT /B 0

:: killmine <PID> <cổng>: chỉ tắt nếu là php.exe/cloudflared.exe nằm trong thư mục cài đặt này.
:killmine
SET AC_PID=%1
powershell -NoProfile -Command "$p = Get-Process -Id $env:AC_PID -ErrorAction SilentlyContinue; if ($p -and $p.Path -and ($p.ProcessName -eq 'php' -or $p.ProcessName -eq 'cloudflared') -and $p.Path.StartsWith($env:AC_ROOT, [StringComparison]::OrdinalIgnoreCase)) { Stop-Process -Id $p.Id -Force; Write-Host ('Da tat Anh Cuoi o cong %2 (PID ' + $p.Id + ').') } elseif ($p) { Write-Host ('Bo qua cong %2 (PID ' + $p.Id + '): ' + $p.Path + ' khong thuoc thu muc nay.') }"
GOTO :eof
