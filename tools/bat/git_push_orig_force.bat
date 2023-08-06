@echo off
REM =================================================
REM This file is part of orkan/utils package
REM (c) @Year@ Orkan https://github.com/orkan/utils
REM =================================================

setlocal
set CMD=git push origin --force

echo -------------------------------------------------------------------------------
echo Git: push orig (force)
echo Cmd: %CMD%
echo -------------------------------------------------------------------------------
%CMD%
