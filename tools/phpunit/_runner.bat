@echo off
REM Note: The CWD is set by calling Tool.bat script to help PHPUnit find the phpunit.xml configuration file.
REM Don't change it!
call %~dp0_header.bat "%~f0"

setlocal
set VENDOR=%~1
set EXTRAS=%~2
set OPTIONS=%~3
set INFILE=%~4

REM Allow arguments: Tool.bat "infile" "extras" -OR- Tool.bat "extras"
if NOT exist "%INFILE%" (
	set EXTRAS=%INFILE%
	set INFILE=
)

REM PHPUnit loc: ----------------------------------------------
for /f "tokens=*" %%x in ( 'call %~dp0_abs.bat "%VENDOR%"' ) do set PHPUNIT=%%x

REM Command: --------------------------------------------------
set COMMAND=%PHPUNIT% %OPTIONS% "%INFILE%"

REM Window mode: ---------------------------------------------
if "%EXTRAS%" == "nowait" (
	set MODE=/c
) else (
	call %~dp0_clip.bat %COMMAND%
	set MODE=/k
)

REM Run: ------------------------------------------------------
set RUN=cmd %MODE% %~dp0_phpunit.bat %COMMAND%

if "%DEBUG%" == "1" (
	echo.
	echo [BAT] %~nx0 %*
	echo [INFILE] %INFILE%
	echo [EXTRAS] %EXTRAS%
	echo [VENDOR] %VENDOR%
	echo [PHPUNIT] %PHPUNIT%
	echo [RUN] %RUN%
)

%RUN%
