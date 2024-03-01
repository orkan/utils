@echo off
REM =============================================================
REM This file is part of the orkan/utils package.
REM Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
REM =============================================================
call %~dp0_config.bat

setlocal
set EXTRAS=%~1
set TESTSUITE=default

%RUNNER_DIR%\testsuite.bat "%VENDOR_DIR%" "%EXTRAS%" "%TESTSUITE%"
