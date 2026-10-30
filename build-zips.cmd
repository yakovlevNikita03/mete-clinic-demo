@echo off
rem METE CLINIC - build theme and plugin archives (double-click to run).
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-zips.ps1" %*
echo.
pause
