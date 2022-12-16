@echo off
pushd %~dp1

setlocal
set INFILE=%1
set EXTRAS=%2

REM Command: --------------------------------------------------
set COMMAND=php -f %INFILE%

REM Window mode: ---------------------------------------------
if "%EXTRAS%" == "nowait" (
	set MODE=/c
) else (
	call %~dp0..\phpunit\_clip.bat %COMMAND%
	set MODE=/k
)

REM Run: ------------------------------------------------------
echo.
cmd %MODE% %COMMAND%

popd
