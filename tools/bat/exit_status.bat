@echo off
REM =================================================
REM Show exit status (c) @Year@ Orkan
REM -------------------------------------------------
REM This file is part of orkan/utils package
REM https://github.com/orkan/utils
REM =================================================

REM Tip: Status codes https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400

echo.
if %ERRORLEVEL% == 0 (
	echo BUILD SUCCESSFUL
	if "%~1" == "quit_on_success" goto :eof
) else (
	echo BUILD FAILED ^(%ERRORLEVEL%^)
)
echo.
pause
