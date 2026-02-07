@ECHO OFF

start cmd /k "cd /d D:\FileZila\erenang && php artisan serve --port=8025"

start cmd /k "cd /d D:\FileZila\erenang && php artisan serve --port=8024"

timeout /t 5 >nul

start firefox http://localhost:8024/admin/administration
