@echo off
setlocal

echo.
echo ******************************************************************
echo  Convert iTunes xml playlist to m3u8
echo  Usage:   %~nx0 ^<playlist.xml^>
echo ******************************************************************
echo.

set RUNNER=%~dpn0.php
set INFILE=%~1
set INEXT=%~x1
set OUTFILE=%~dpn1.m3u8

if not exist "%INFILE%" (
	call :error "Playlist XML file not found."
	goto :end
)
if "%INEXT%" NEQ ".xml" (
	call :error "Only XML playlist file suported."
	goto :end
)

REM Commands: -------------------------------------------------
php -f %RUNNER% "%INFILE%" > "%OUTFILE%"
goto :end

:error
echo Error: %~1
exit /b

:end
more "%OUTFILE%"
echo.
pause
