@echo off
REM =================================================
REM CryptoBox DVR dir rename (c) @Year@ Orkan
REM -------------------------------------------------
REM This file is part of orkan/utils package
REM https://github.com/orkan/utils
REM =================================================

REM Set working dir to current dir b/c is not set when running from context menu!

setlocal
pushd %~dp0

echo **************************************************************************
echo    Tool: CryptoBox DVR dir rename
echo   Usage: %~nx0 ^<DVR dir^>
echo Example: %~nx0 "C:\DVR dir"
echo **************************************************************************
echo.

REM Import: --------------------------------------------
REM For shortcuts (*.lnk) the dropped file path is always added at the end (by Windows)
REM So <DVR dir> must be the last param
set HOMEDIR=%~1

REM Delegate job to PHP...
php %~n0.php "%HOMEDIR%"

REM Show BUILD status
call ..\bat\exit_status.bat
