@echo off
REM =================================================
REM This file is part of orkan/utils package
REM (c) @Year@ Orkan https://github.com/orkan/utils
REM =================================================

REM https://stackoverflow.com/questions/30590083/how-do-i-rename-both-a-git-local-and-remote-branch-name

setlocal
for /f "tokens=*" %%x in ( 'git rev-parse --abbrev-ref HEAD' ) do set OLD=%%x
set /p NEW=Git: on branch '%OLD%' -^> rename to: 

echo.
echo ===============================================================================
echo Git: rename branch local & remote
echo ===============================================================================

echo.
echo -------------------------------------------------------------------------------
echo Step 1: Rename...
set CMD=git branch -m %NEW%
echo %CMD%
%CMD%

echo.
echo -------------------------------------------------------------------------------
echo Step 2: Delete old remote branch '%OLD%' and push new branch '%NEW%'...
set CMD=git push origin :%OLD% %NEW%
echo %CMD%
%CMD%

echo.
echo -------------------------------------------------------------------------------
echo Step 3: Reset upstream...
set CMD=git push origin -u %NEW%
echo %CMD%
%CMD%

echo.
echo ===============================================================================
echo Bye!
echo.
