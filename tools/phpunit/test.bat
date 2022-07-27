@echo off
call %~dp0_header.bat "%~f0"

setlocal
set VENDOR=%~1
set EXTRAS=%~2
set INFILE=%~3

REM Config: ---------------------------------------------------
set OPTIONS=--stop-on-error

call %~dp0_runner.bat "%VENDOR%" "%EXTRAS%" "%OPTIONS%" "%INFILE%"
