@echo off
REM =============================================================
REM This file is part of the orkan/utils package.
REM Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
REM =============================================================
call %~dp0_config.bat

REM test.bat [vendor_dir] [extra] [infile]
%RUNNER_DIR%\phpunit.bat %VENDOR_DIR% "" %*
