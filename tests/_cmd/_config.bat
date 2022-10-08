@echo off

REM Turn debug/verbose mode for this script
set DEBUG=

REM These paths are correct for location:
REM vendor\[vendor_name]\[package_name]\tests\_cmd\_config.bat
set VENDOR_DIR=%~dp0..\..\..\..\..\vendor
set RUNNER_DIR=%VENDOR_DIR%\orkan\utils\tools\phpunit

if not exist "%VENDOR_DIR%" (
	echo [%~nx0] VENDOR_DIR not found! Using: "%VENDOR_DIR%"
	exit /b 404
)
if not exist "%RUNNER_DIR%" (
	echo [%~nx0] RUNNER_DIR not found! Using: "%RUNNER_DIR%"
	exit /b 404
)
