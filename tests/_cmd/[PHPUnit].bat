@echo off
call %~dp0_config.bat

setlocal
set INFILE=%~1
set EXTRAS=%~2

%RUNNER_DIR%\test.bat "%VENDOR_DIR%" "%EXTRAS%" "%INFILE%"
