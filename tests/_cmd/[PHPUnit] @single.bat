@echo off
REM =============================================================
REM This file is part of the orkan/utils package.
REM Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
REM =============================================================
call %~dp0_config.bat

setlocal
set INFILE=%~1
set EXTRAS=%~2
set TESTGROUP=single

%RUNNER_DIR%\testgroup.bat "%VENDOR_DIR%" "%EXTRAS%" "%TESTGROUP%" "%INFILE%"
