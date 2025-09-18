@echo off

setlocal
set "LABEL=%~1"
set "NOWAIT=%~2"
set APPDIR=%CD%
set TOOLS=%~dp0..\bat

echo **********************************************************************************************
echo    Tool: HWInfo - restore settings
echo   Usage: %~nx0 [label] [nowait]
echo **********************************************************************************************
echo Inputs:
echo   APPDIR: "%APPDIR%"
echo    LABEL: "%LABEL%"
echo   NOWAIT: "%NOWAIT%"
echo.

:label
if "%LABEL%" == "" set /p "LABEL=Settings label: "
if "%LABEL%" == "" (
	echo Error: No label!
	goto :label
)

set OUTNAME=%COMPUTERNAME%_%LABEL%

echo.
echo ---------------------
echo Restore registry key:
echo ---------------------
reg import "%APPDIR%\%OUTNAME%.reg"

echo.
echo -----------------
echo Restore INI file:
echo -----------------
copy /Y "%APPDIR%\%OUTNAME%.ini" "%APPDIR%\HWiNFO64.INI"

REM -----------------------------------------------------------------------------------------------
call "%~dp0..\bat\exit_status.bat" "%NOWAIT%"
exit /b %ERRORLEVEL%
