@echo off
setlocal

REM Config: --------------------------------------------
set SOURCE=%~1
set EXTRAS=%~2
set EOL=%3
set EXCLUDES=%~4
set INCLUDES=%~5

REM Command: -------------------------------------------
set COMMAND=ant -DSourceDir="%SOURCE%" -DExcludes="%EXCLUDES%" -DIncludes="%INCLUDES%" -DEOL="%EOL%" -f "%~dpn0.xml"

REM Run: -----------------------------------------------
echo.
cmd /C %COMMAND%

REM ----------------------------------------------------
:end
if "%EXTRAS%" NEQ "nowait" pause
exit /B %ERRORLEVEL%
