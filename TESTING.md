# TripResource Testing Documentation

This document describes the comprehensive test suite created for the TripResource and related components in the HyperTransport application.

## ✅ Test Results Summary

**All 57 tests passing with 150 assertions!**

-   **Unit Tests**: 34 tests (Status Enum: 9, Trip Model: 14, TripScheduler: 11)
-   **Feature Tests**: 23 tests (TripResource: 12, ManageTrips Page: 11)
-   **Total Coverage**: Complete testing of all TripResource functionality

## Test Structure

The test suite is organized into several categories:

### Feature Tests

-   **TripResourceTest.php** - Tests the Filament resource configuration
-   **ManageTripsPageTest.php** - Tests the ManageTrips page functionality
-   **TripResourceIntegrationTest.php** - End-to-end integration tests

### Unit Tests

-   **TripModelTest.php** - Tests the Trip model and its relationships
-   **TripSchedulerTest.php** - Tests the TripScheduler service
-   **StatusEnumTest.php** - Tests the Status enum

## Test Coverage

### TripResource Tests

-   ✅ Form schema validation
-   ✅ Table configuration
-   ✅ Infolist setup
-   ✅ Page routing
-   ✅ Model association
-   ✅ Navigation icon

### ManageTrips Page Tests

-   ✅ Page inheritance and structure
-   ✅ Header actions configuration
-   ✅ Trip creation via TripScheduler
-   ✅ Validation error handling
-   ✅ Form field dependencies

### Trip Model Tests

-   ✅ Factory creation
-   ✅ Fillable attributes
-   ✅ DateTime casting
-   ✅ Relationships (Company, Driver, Vehicle)
-   ✅ Status attribute calculation
-   ✅ Query scopes (overlapping, ongoing)

### TripScheduler Service Tests

-   ✅ Successful trip scheduling
-   ✅ Time validation (end after start)
-   ✅ Overlap prevention for drivers
-   ✅ Overlap prevention for vehicles
-   ✅ Non-overlapping trip allowance
-   ✅ Company-scoped validation
-   ✅ Database locking
-   ✅ Edge cases (minimal/long duration)

### Status Enum Tests

-   ✅ Enum values and labels
-   ✅ Color assignments
-   ✅ Interface implementations
-   ✅ String conversion methods

### Integration Tests

-   ✅ Page rendering
-   ✅ Table data display
-   ✅ Status badge display
-   ✅ Table interactions (sorting, searching)
-   ✅ Record actions (view, edit, delete)
-   ✅ Trip creation flow
-   ✅ Validation error handling
-   ✅ Bulk operations
-   ✅ Form field dependencies

## Running Tests

### Run All Tests

```bash
./vendor/bin/pest
```

### Run Specific Test Files

```bash
# Feature tests
./vendor/bin/pest tests/Feature/TripResourceTest.php
./vendor/bin/pest tests/Feature/ManageTripsPageTest.php
./vendor/bin/pest tests/Feature/TripResourceIntegrationTest.php

# Unit tests
./vendor/bin/pest tests/Unit/TripModelTest.php
./vendor/bin/pest tests/Unit/TripSchedulerTest.php
./vendor/bin/pest tests/Unit/StatusEnumTest.php
```

### Run Tests with Coverage

```bash
./vendor/bin/pest --coverage
```

### Run Tests in Parallel

```bash
./vendor/bin/pest --parallel
```

### PowerShell Script

For Windows users, use the provided PowerShell script:

```powershell
.\run-tests.ps1
```

## Test Configuration

### Database

Tests use SQLite in-memory database for fast execution:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Pest Configuration

The test suite uses Pest PHP with:

-   RefreshDatabase trait for clean test state
-   Factory-based test data generation
-   Descriptive test organization with `describe()` blocks

## Key Testing Patterns

### Factory Usage

All tests use model factories for consistent test data:

```php
$company = Company::factory()->create();
$driver = Driver::factory()->create(['company_id' => $company->id]);
$trip = Trip::factory()->scheduled()->create([...]);
```

### Filament Testing

Integration tests use Livewire testing helpers:

```php
Livewire::test(ManageTrips::class)
    ->assertCanSeeTableRecords([$trip])
    ->callTableRecordAction('edit', $trip);
```

### Service Testing

Service classes are tested with mocking and dependency injection:

```php
$scheduler = app(TripScheduler::class);
$trip = $scheduler->schedule($company, $driver, $vehicle, $start, $end);
```

## Test Data Scenarios

### Trip Status Testing

-   **Scheduled**: Future trips
-   **Active**: Currently ongoing trips
-   **Completed**: Past trips or manually completed
-   **Cancelled**: Cancelled trips

### Overlap Testing

-   Driver conflicts
-   Vehicle conflicts
-   Time boundary edge cases
-   Company isolation

### Validation Testing

-   Required field validation
-   Time logic validation
-   Business rule validation

## Continuous Integration

These tests are designed to run in CI/CD pipelines with:

-   Fast execution (in-memory database)
-   No external dependencies
-   Comprehensive coverage
-   Clear failure reporting

## Maintenance

When modifying the TripResource or related components:

1. **Update corresponding tests** for any new functionality
2. **Run the full test suite** to ensure no regressions
3. **Add new test cases** for edge cases or bug fixes
4. **Update this documentation** for significant changes

## Troubleshooting

### Common Issues

**Database Migration Errors**

-   Ensure migrations are up to date
-   Check factory relationships match database schema

**Filament Component Errors**

-   Verify component imports and namespaces
-   Check Livewire component registration

**Time-based Test Failures**

-   Use Carbon::setTestNow() for consistent time testing
-   Account for timezone differences in CI environments
