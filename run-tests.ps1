# PowerShell script to run TripResource tests

Write-Host "Running TripResource Tests..." -ForegroundColor Green

# Run all tests
Write-Host "`nRunning all tests..." -ForegroundColor Yellow
./vendor/bin/pest

# Run specific test files
Write-Host "`nRunning TripResource tests..." -ForegroundColor Yellow
./vendor/bin/pest tests/Feature/TripResourceTest.php

Write-Host "`nRunning ManageTrips page tests..." -ForegroundColor Yellow
./vendor/bin/pest tests/Feature/ManageTripsPageTest.php

Write-Host "`nRunning Trip model tests..." -ForegroundColor Yellow
./vendor/bin/pest tests/Unit/TripModelTest.php

Write-Host "`nRunning TripScheduler service tests..." -ForegroundColor Yellow
./vendor/bin/pest tests/Unit/TripSchedulerTest.php

Write-Host "`nRunning Status enum tests..." -ForegroundColor Yellow
./vendor/bin/pest tests/Unit/StatusEnumTest.php

Write-Host "`nRunning integration tests..." -ForegroundColor Yellow
./vendor/bin/pest tests/Feature/TripResourceIntegrationTest.php

Write-Host "`nTest run completed!" -ForegroundColor Green
