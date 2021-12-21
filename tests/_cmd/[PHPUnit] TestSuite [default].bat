@echo off
call %~dp0_config.bat

REM testsuite.bat [vendor_dir] [extra] [testsuite]
%RUNNER_DIR%\testsuite.bat %VENDOR_DIR% "" default
