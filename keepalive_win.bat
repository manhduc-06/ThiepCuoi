@echo off
REM keepalive_win.bat — giu Anh Cuoi chay lien tuc: server thoat thi tu chay lai. Dong cua so de dung.
setlocal EnableDelayedExpansion
SET RUN=%~dp0run_window.bat
echo [keepalive] Giam sat Anh Cuoi. Dong cua so de dung han.
:loop
echo [keepalive] !TIME! khoi dong...
call "%RUN%" < nul
echo [keepalive] Server thoat - chay lai sau 5s.
timeout /t 5 /nobreak >nul
GOTO loop
