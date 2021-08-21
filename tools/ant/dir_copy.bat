@echo off
setlocal

REM Config: --------------------------------------------
set SOURCE=%~1
set TARGET=%~2
set EXTRAS=%~3
set CONTINUE=%~4
set EXCLUDES=%~5
set INCLUDES=%~6

REM Confirm: -------------------------------------------
if "%CONTINUE%" NEQ "" (
	set /p ANSWER=%CONTINUE% [y/N]:
)
if "%CONTINUE%" NEQ "" (
	if "%ANSWER%" NEQ "y" (
		goto :end
	)
)

REM Command: -------------------------------------------
set COMMAND=ant -DSourceDir="%SOURCE%" -DTargetDir="%TARGET%" -DExcludes="%EXCLUDES%" -DIncludes="%INCLUDES%" -f "%~dpn0.xml"

REM Run: -----------------------------------------------
echo.
cmd /c %COMMAND%

REM ----------------------------------------------------
:end
if "%EXTRAS%" == "nowait" (
	exit /b
) else (
	pause
)
