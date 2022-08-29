@echo off
call %~dp0_header.bat "%~f0"

REM Setup xdebug: ---------------------------------------------
set XDEBUG_MODE=coverage

setlocal
set VENDOR=%~1
set EXTRAS=%~2
set OUTDIR=%~3

REM Clear coverag dir: ----------------------------------------
if exist "%OUTDIR%" (
	rd /s /q "%OUTDIR%"
)

REM Config: ---------------------------------------------------
set OPTIONS=--coverage-html "%OUTDIR%"

call %~dp0_runner.bat "%VENDOR%" "%EXTRAS%" "%OPTIONS%"
