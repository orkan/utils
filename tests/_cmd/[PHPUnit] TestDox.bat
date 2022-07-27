@echo off
call %~dp0_config.bat

REM Set EXTRAS on %2 since drag&drop always use %1
setlocal
set INFILE=%~1
set EXTRAS=%~2

%RUNNER_DIR%\testdox.bat "%VENDOR_DIR%" "%EXTRAS%" "%INFILE%"
