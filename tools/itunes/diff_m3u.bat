@echo off
setlocal

echo.
echo ******************************************************************
echo  Find difference between two playlists
echo  Usage:   %~nx0 ^<playlist.m3u8^> ^<playlist.m3u8^>
echo ******************************************************************
echo.

set RUNNER=%~dpn0.php
set OUTFILE=%~dp1\%~n0.m3u8

REM Commands: -------------------------------------------------
php -f %RUNNER% %* > "%OUTFILE%"
more "%OUTFILE%"
echo.
pause
