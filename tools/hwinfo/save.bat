@echo off

setlocal
set "LABEL=%~1"
set "NOWAIT=%~2"
set APPDIR=%CD%
set TOOLS=%~dp0..\bat

echo **********************************************************************************************
echo    Tool: HWInfo - save settings
echo   Usage: %~nx0 [label] [nowait]
echo **********************************************************************************************
echo Inputs:
echo   APPDIR: "%APPDIR%"
echo    LABEL: "%LABEL%"
echo   NOWAIT: "%NOWAIT%"
echo.

REM call %TOOLS%\functions.bat date
REM set _DATETIME=_%DATETIME%

if "%LABEL%" == "" if "%NOWAIT%" NEQ "nowait" set /p "LABEL=Settings label: "
if "%LABEL%" NEQ "" set LABEL=_%LABEL%

REM set OUTNAME=_%COMPUTERNAME%%_LABEL%%_DATETIME%
set OUTNAME=%COMPUTERNAME%%LABEL%

echo.
echo ------------------
echo Save registry key:
echo ------------------
call "%TOOLS%\reg_export.bat" HKCU\SOFTWARE\HWiNFO64 "%APPDIR%\%OUTNAME%.reg" nowait

echo.
echo --------------
echo Save INI file:
echo --------------
copy /Y "%APPDIR%\HWiNFO64.INI" "%APPDIR%\%OUTNAME%.ini"

REM -----------------------------------------------------------------------------------------------
call "%~dp0..\bat\exit_status.bat" "%NOWAIT%"
exit /b %ERRORLEVEL%
