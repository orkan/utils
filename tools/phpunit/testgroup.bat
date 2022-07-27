@echo off
call %~dp0_header.bat "%~f0"

setlocal
set VENDOR=%~1
set EXTRAS=%~2
set TESTGROUP=%~3
set INFILE=%~4

REM Config: ---------------------------------------------------
set OPTIONS=--testdox --group %TESTGROUP%

echo.
echo PHPUnit Group: %TESTGROUP%
echo =============

call %~dp0_runner.bat "%VENDOR%" "%EXTRAS%" "%OPTIONS%" "%INFILE%"
