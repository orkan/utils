@echo off

REM Turn debug/verbose mode for this script
set DEBUG=

REM These paths are correct for location:
REM vendor\[vendor_name]\[package_name]\tests\_cmd\_config.bat
set RUNNER_DIR=%~dp0..\..\..\utils\tools\phpunit
set VENDOR_DIR=%~dp0..\..\..\..\..\vendor
