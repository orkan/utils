@echo off

setlocal
set "REGKEY=%~1"
set "OUTFILE=%~2"
set "NOWAIT=%~3"
if "%OUTFILE%" == "" set OUTFILE=%~dpn0.reg

echo **********************************************************************************************
echo   Export registry Key:
echo   %REGKEY% =^>
echo   "%OUTFILE%"
echo.
echo   Usage:   %~nx0 ^<Keyname^> [Filename] [nowait]
echo   Example: %~nx0 HKLM\Software\MyCo\MyApp AppBkUp.reg
echo **********************************************************************************************
echo Inputs:
echo    REGKEY: "%REGKEY%"
echo   OUTFILE: "%OUTFILE%"
echo    NOWAIT: "%NOWAIT%"
echo.

if "%REGKEY%" == "" (
	set COMMAND=echo Error: Missing Keyname!
) else (
	set COMMAND=reg export "%REGKEY%" "%OUTFILE%" /y
)

%COMMAND%

REM -----------------------------------------------------------------------------------------------
call %~dp0exit_status.bat "%NOWAIT%"
exit /b %ERRORLEVEL%
