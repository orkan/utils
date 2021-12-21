@echo off
REM Note:
REM CWD is set by calle to help PHPUnit find phpunit.xml configuration file.
REM Don't change it!

call %~dp0_header.bat "%~f0"

setlocal
set VENDOR=%1
set EXTRAS=%2
set OPTIONS=%~3
set INFILE=%4

REM PHPUnit loc: ----------------------------------------------
for /f "tokens=*" %%x in ( 'call %~dp0_abs.bat %VENDOR%' ) do set PHPUNIT=%%x

REM Command: --------------------------------------------------
set COMMAND=%PHPUNIT% %OPTIONS% %INFILE%

REM Switch: ---------------------------------------------------
if "%EXTRAS%" == "nowait" (
	set SWITCH=/c
) else (
	call %~dp0_clip.bat %COMMAND%
	set SWITCH=/k
)

REM Run: ------------------------------------------------------
set RUN=cmd %SWITCH% %~dp0_phpunit.bat %COMMAND%

if "%DEBUG%" == "1" (
	echo.
	echo [VENDOR] %VENDOR%
	echo [PHPUNIT] %PHPUNIT%
	echo [RUN] %RUN%
)

%RUN%
