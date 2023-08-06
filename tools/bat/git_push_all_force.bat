@echo off
REM =================================================
REM This file is part of orkan/utils package
REM (c) @Year@ Orkan https://github.com/orkan/utils
REM =================================================

echo.
echo ===============================================================================
echo Git: push all (force)
echo Dir: %CD%
echo ===============================================================================

echo.
call %~dp0git_push_orig_force.bat

echo.
call %~dp0git_push_tags_force.bat

echo.
echo ===============================================================================
echo Bye!
echo.
