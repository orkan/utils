@echo off
setlocal

REM Config: -------------------------------------------------
set BUILDFILE=%~dpn0.xml
set TARGET=%1
set EXTRAS=%2

REM Commands: -------------------------------------------------
set COMMAND=ant -DTargetDir = %TARGET% -f %BUILDFILE%
REM -----------------------------------------------------------

echo.
cmd /C %COMMAND%

REM -----------------------------------------------------------
:end
if "%EXTRAS%" NEQ "nowait" pause
exit /B %ERRORLEVEL%
