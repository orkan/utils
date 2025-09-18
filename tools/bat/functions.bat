@echo off
goto %1 %*

REM Sleep for X sec
:sleep
setlocal EnableDelayedExpansion
for /F %%z in ('copy /Z "%~dpf0" nul') do set "CR=%%z"
for /L %%i in (%2 -1 0) do (
	set TEXT=Wait %%i sec    
	set /p "=!TEXT!!CR!" <nul
	TITLE !TEXT!
	if %%i NEQ 0 timeout /t 1 >nul
)
setlocal DisableDelayedExpansion
goto :eof

REM Create date-time unique string, eg. 2022011209032911
:datetime
set DATETIME=%DATE%.%TIME: =0%
for /f "tokens=1-7 delims=/:.," %%A in ("%DATETIME%") do set DATETIME=%%C%%B%%A%%D%%E%%F%%G
goto :eof

REM Get datetime string
:date
set DATETIME=%DATE%.%TIME: =0%
for /f "tokens=1-7 delims=/:.," %%A in ("%DATETIME%") do set DATETIME=%%C%%B%%A
goto :eof
