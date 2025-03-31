@echo off
setlocal

echo ***************************************************
echo   XAMPP MySQL import script
echo   Usage: %~nx0 ^<database^> ^<sql file^>
echo ***************************************************
echo.

REM Import: -------------------------------------------
set MYSQL_DB=%1
set MYSQL_FILE=%2

REM Config: -------------------------------------------------
set MYSQL_PATH=C:\mysql\xampp\mysql\bin\mysql.exe
set MYSQL_HOST=3307
set MYSQL_PORT=3307
set MYSQL_USER=root
set COMMAND=%MYSQL_PATH% -h 127.0.0.1 --port=3307 -u root %MYSQL_DB%

REM Display: ------------------------------------------
echo Inputs:
echo database : [%MYSQL_DB%]
echo sql file : [%MYSQL_FILE%]
echo.

echo Command:
echo %COMMAND% ^< %MYSQL_FILE%
echo.

if "%MYSQL_DB%" == "" goto error
if "%MYSQL_FILE%" == "" goto error
if not exist %MYSQL_FILE% goto error
pause

REM Run: ------------------------------------------------------
%COMMAND% < %MYSQL_FILE%

if %ERRORLEVEL% NEQ 0 (
	echo BUILD FAILED
)

goto :end

:error
echo Wrong parameters

:end
echo.
pause
