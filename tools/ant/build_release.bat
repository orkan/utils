@echo off
setlocal

REM Config: --------------------------------------------
set SOURCE=%~1
set INCLUDES=%~2
set EXTRAS=%~3
set CONTINUE=%~4

if not exist %SOURCE% (
	echo Source dir not found: %SOURCE%
	exit /b 1
)
pushd %SOURCE%
echo %CD%

REM Confirm: -------------------------------------------
if "%CONTINUE%" NEQ "" (
	set /p ANSWER=%CONTINUE% [y/N]:
)
if "%CONTINUE%" NEQ "" (
	if "%ANSWER%" NEQ "y" (
		goto :eof
	)
)

REM echo Last commit:
REM git rev-parse --short HEAD
echo Last release:
git describe --tags --abbrev=0

:get_version
set /p VERSION=New release version: 
if "%VERSION%" == "" goto :get_version

set /p MESSAGE=Release message (no commit if empty): 

REM Run: -----------------------------------------------
echo.
cmd /c ant -DSourceDir="%SOURCE%" -DIncludes="%INCLUDES%" -DVersion="%VERSION%" -f "%~dpn0.xml"

REM Git: -----------------------------------------------
if "%MESSAGE%" == "" goto :end

echo.
git add *
git commit -m "%MESSAGE%"
git tag "%VERSION%" -m "%MESSAGE%"

REM ----------------------------------------------------
:end
popd
if "%EXTRAS%" == "nowait" (
	exit /b
) else (
	pause
)
