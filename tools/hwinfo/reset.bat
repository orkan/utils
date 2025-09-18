@echo off

setlocal
set "NOWAIT=%~1"
set APPDIR=%CD%

echo **********************************************************************************************
echo    Tool: HWInfo - reset settings
echo   Usage: %~nx0 [nowait]
echo **********************************************************************************************
echo Inputs:
echo   APPDIR: "%APPDIR%"
echo   NOWAIT: "%NOWAIT%"
echo.

echo.
echo -------------------
echo Reset registry key:
echo -------------------
reg import "%~dp0reset.reg"

echo.
echo ---------------
echo Reset INI file:
echo ---------------
copy /Y "%~dp0reset.ini" "%APPDIR%\HWiNFO64.INI"

REM -----------------------------------------------------------------------------------------------
call "%~dp0..\bat\exit_status.bat" "%NOWAIT%"
exit /b %ERRORLEVEL%
