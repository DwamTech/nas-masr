Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Running Free Plan Logic Tests" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Change to script directory
Set-Location $PSScriptRoot

Write-Host "Running tests..." -ForegroundColor Yellow
Write-Host ""

# Run the tests
php artisan test --filter=ListingCreationWithFreePlanTest

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Tests Completed" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Wait for user input
Write-Host "Press any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
