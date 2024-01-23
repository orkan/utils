@echo off
call %~dp0_header.bat "%~f0"

setlocal
set VENDOR=%~1
set EXTRAS=%~2
set OPTIONS=%~3

call %~dp0_runner.bat "%VENDOR%" "%EXTRAS%" "%OPTIONS%"
