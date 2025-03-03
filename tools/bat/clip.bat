@echo off

setlocal
set COMMAND=%*

echo ----------
echo %COMMAND%
echo ----------

REM echo.
REM echo Use right mouse button to run again!

REM Adds new line at the end
REM echo %COMMAND%| clip

REM Fixes new line problem
<nul set /p "=%COMMAND%" | clip
