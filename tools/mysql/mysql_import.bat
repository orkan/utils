@echo off

setlocal
set DBNAME=%~1
set DBFILE=%~2
set DBTOOL=C:\mysql\bin\mysql.exe
set DBUSER=root
set COMMAND=%DBTOOL% -u root -p %DBNAME%

echo **********************************************************************************************
echo   MySQL import
echo   Usage: %~nx0 ^<database^> ^<sql file^>
echo **********************************************************************************************
echo Inputs:
echo   DBTOOL: "%DBTOOL%"
echo   DBUSER: "%DBUSER%"
echo   DBNAME: "%DBNAME%"
echo   DBFILE: "%DBFILE%"
echo.
pause

%COMMAND% < "%DBFILE%"

call %~dp0..\bat\exit_status.bat
