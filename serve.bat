@echo off
REM Starts the Laravel dev server with upload limits raised to 26MB
REM (storage\php-conf\zzz-uploads.ini via PHP_INI_SCAN_DIR).
set PHP_INI_SCAN_DIR=%~dp0storage\php-conf
cd /d "%~dp0"
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
