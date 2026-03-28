@echo off
echo Surveillance des logs WhatsApp en temps reel...
echo Appuyez sur Ctrl+C pour arreter
echo.
powershell -Command "Get-Content 'storage\logs\laravel.log' -Wait -Tail 20 | Select-String -Pattern 'WhatsApp'"
