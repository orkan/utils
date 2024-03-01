@echo off
REM =============================================================
REM This file is part of the orkan/utils package.
REM Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
REM =============================================================
call %~dp0_config.bat

setlocal
set INFILE=%~1
set EXTRAS=%~2

%RUNNER_DIR%\test.bat "%VENDOR_DIR%" "%EXTRAS%" "%INFILE%"
