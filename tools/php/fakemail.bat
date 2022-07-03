@echo off
setlocal

rem Help:
rem https://stackoverflow.com/questions/4870284/have-php-mail-function-output-to-a-local-file-instead-of-using-smtp
rem https://stackoverflow.com/questions/11438628/replace-php-mail-function-with-customised-version

rem Setup:
rem php.ini > sendmail_path = fakemail.bat
rem PHP mail() will output email body to STDOUT
rem the 5th param of mail() is under %1

::create date strings
for /f "tokens=1-3 delims=/." %%a in ("%DATE%") do (set mail_date=%%c%%b%%a)
for /f "tokens=1-3 delims=/:" %%a in ("%TIME%") do (set mail_time=%%a%%b%%c)
for /f "tokens=1-2 delims=/," %%a in ("%mail_time%") do (set mail_time=%%a)
set mail_time=%mail_time: =0%

set mail_dir=%TEMP%\sendmail\%mail_date%
if not exist %mail_dir% (
	mkdir %mail_dir%
	set mail_open=1
)

::make filename unique
:random
set mail_file="%mail_dir%\mail_%mail_date%_%mail_time%.[%RANDOM%].txt"
if exist %mail_file% goto :random

::save email to file
echo Mailer: %0 %1 > %mail_file%

::get STDOUT from PHP
more >> %mail_file%

if defined mail_open (
	start %mail_dir%
)
