@echo off
setlocal

REM Config: --------------------------------------------
set SOURCE=%~1
set EXTRAS=%~2

if not exist %SOURCE% (
	echo Source dir not found: %SOURCE%
	exit /B 1
)

REM Run: -----------------------------------------------
echo.
cmd /C ant -DSourceDir="%SOURCE%" -f "%~dpn0.xml"

REM ----------------------------------------------------
:end
REM popd
if "%EXTRAS%" NEQ "nowait" pause
exit /B %ERRORLEVEL%
