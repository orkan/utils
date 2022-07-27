@echo off
call %~dp0_config.bat

setlocal
set EXTRAS=%~1
set TESTSUITE=default

%RUNNER_DIR%\testsuite.bat "%VENDOR_DIR%" "%EXTRAS%" "%TESTSUITE%"
