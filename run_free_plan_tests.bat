@echo off
echo ========================================
echo Running Free Plan Logic Tests
echo ========================================
echo.

REM Change to script directory
cd /d "%~dp0"

echo Running tests...
echo.

php artisan test --filter=ListingCreationWithFreePlanTest

echo.
echo ========================================
echo Tests Completed
echo ========================================
echo.
echo Press any key to exit...
pause > nul
