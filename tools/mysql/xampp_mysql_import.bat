@echo off
echo %~0
setlocal

echo ***************************************************
echo   XAMPP MySQL import script
echo   Usage: %~nx0 username database "path to sql file"
echo ***************************************************

REM Config: -------------------------------------------------
set MYSQL_HOME=C:\mysql\xampp\mysql
set MYSQL_USER=%1
set MYSQL_DB=%2
set MYSQL_FILE=%~3
echo.

REM Commands: -------------------------------------------------
set  COMMAND= %MYSQL_HOME%\bin\mysql.exe -u %MYSQL_USER% -p %MYSQL_DB% ^< "%MYSQL_FILE%"
echo COMMAND: %MYSQL_HOME%\bin\mysql.exe -u %MYSQL_USER% -p %MYSQL_DB% ^< "%MYSQL_FILE%"
REM echo %COMMAND%
REM Can't echo %COMMAND% ??? "The syntax of the command is incorrect."
REM -----------------------------------------------------------

if "%MYSQL_USER%" == "" goto error
if "%MYSQL_DB%" == "" goto error
if "%MYSQL_FILE%" == "" goto error
if not exist %MYSQL_FILE% goto error

cmd /k %COMMAND%

:error
echo Wrong parameters
