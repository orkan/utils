@echo off
REM =================================================
REM Show exit status
REM https://github.com/orkan/utils
REM -------------------------------------------------
REM This file is part of orkan/utils package
REM Copyright (c) 2024 Orkan <orkans+utils@gmail.com>
REM =================================================

REM Status codes https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400
REM Dont use setlocal here cos it resets ERRORLEVEL !!!

if %ERRORLEVEL% == 0 (
	echo.
	echo BUILD SUCCESSFUL
	echo.
	if "%~1" == "nowait" goto :eof
) else (
	echo.
	echo BUILD FAILED ^(%ERRORLEVEL%^)
	REM Clear ERRORLEVEL
	REM ver > nul
	REM Always pause on errors!
	echo.
)

pause
