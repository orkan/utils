@echo off
setlocal

echo.
echo ******************************************************************
echo  Export lines from Eclipse preference file using a given prefix
echo  Usage:   %~nx0 ^<preference file^>
echo  Example: %~nx0 eclipse.epf
echo ******************************************************************
echo.

if not exist "%~1" (
	call :error "Export [x] ALL preferences from Eclipse first, then drop it on this file."
	goto :end
)
if "%~x1" NEQ ".epf" (
	call :error "Only Eclipse EPF (preference) file suported."
	goto :end
)

set HOME=%~dp1
set RUNNER=%~dpn0.php
set INFILE=%~1
set INBASE=%~n1

echo Home: %HOME%
echo.

REM Commands: -------------------------------------------------
call :run "Colors and fonts - Git"	/instance/org.eclipse.ui.workbench/org.eclipse.egit.ui.
call :run "Editors - Annotations"	/instance/org.eclipse.ui.editors/

goto :end

REM Functions: ------------------------------------------------
:run
set NAME=%~1
set NEEDLE=%~2
set OUTNAME=%INBASE%.[%NAME%].txt
echo Exporting: %OUTNAME%
php -f %RUNNER% "%INFILE%" "%NEEDLE%" > "%HOME%%OUTNAME%"
exit /b

:error
echo Error: %~1
exit /b

:end
echo.
pause
