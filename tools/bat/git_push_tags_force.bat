@echo off
REM =================================================
REM This file is part of orkan/utils package
REM (c) @Year@ Orkan https://github.com/orkan/utils
REM =================================================

setlocal
set CMD=git push origin --tags --force

echo -------------------------------------------------------------------------------
echo Git: push tags (force)
echo Cmd: %CMD%
echo -------------------------------------------------------------------------------
%CMD%
