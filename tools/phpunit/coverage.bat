@echo off
call %~dp0_header.bat "%~f0"

setlocal
set VENDOR=%~1
set EXTRAS=%~2
set OUTDIR=%~3

REM Clear coverag dir: ----------------------------------------
rd /s /q %OUTDIR%

REM Config: ---------------------------------------------------
set OPTIONS=--coverage-html %OUTDIR%

call %~dp0_runner.bat "%VENDOR%" "%EXTRAS%" "%OPTIONS%"
