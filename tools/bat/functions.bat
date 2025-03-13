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
