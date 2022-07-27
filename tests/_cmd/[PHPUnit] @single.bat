@echo off
call %~dp0_config.bat

setlocal
set INFILE=%~1
set EXTRAS=%~2
set TESTGROUP=single

%RUNNER_DIR%\testgroup.bat "%VENDOR_DIR%" "%EXTRAS%" "%TESTGROUP%" "%INFILE%"
