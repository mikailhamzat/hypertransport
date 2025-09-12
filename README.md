# HyperTransport 🚐

A transportation management system built with **Laravel 12** and **Filament 4** for the **Hypersender Coding Challenge**.

---

[![Build Status](https://github.com/mikailhamzat/hypertransport/actions/workflows/tests.yml/badge.svg)](https://github.com/mikailhamzat/hypertransport/actions)
[![License](https://img.shields.io/github/license/mikailhamzat/hypertransport)](./LICENSE)

---

## 📌 Assumptions

-   A trip lasts **1–8 hours** by default.
-   **Overlap rule**:

    -   A driver cannot be assigned to more than one trip that overlaps in time.
    -   A vehicle cannot be assigned to more than one trip that overlaps in time.

-   **Trip status life-cycle**:
    `scheduled → active (when current time enters window) → completed`
-   **KPIs** are recalculated every 60 seconds (cached) for performance.
-   A single admin user manages all companies (not multi-tenant SaaS, just company-scoped data in one DB).

---

## 🏗️ Design Decisions

### Service Layer

-   Business logic like preventing overlapping trips is handled in dedicated service classes (`TripSchedulerService`) for testability and clarity.

### Scopes

-   Custom Eloquent scopes (`overlapping()`, `availableBetween()`, `ongoing()`) make queries reusable and expressive.

### Caching

-   KPIs (active trips, available drivers, trips completed) are cached for **60s** to reduce repeated heavy queries on the dashboard.

### Indexes

-   `company_id` on drivers, vehicles, and trips for faster company lookups.
-   Unique indexes on `drivers.license_number` and `vehicles.plate_number`.
-   Composite index on `trips (driver_id, start_at, end_at)` and `(vehicle_id, start_at, end_at)` to speed up overlap checks.

---

## ⚙️ Setup

```bash
# Clone repo
git clone https://github.com/mikailhamzat/hypertransport.git
cd hypertransport

# Install dependencies
composer install
npm install && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate --seed

# Create a test user
php artisan make:filament-user

# Run the app
php artisan serve
```

---

## 📊 Features

-   **Dashboard KPIs**: active trips, available drivers/vehicles, monthly completed trips.
-   **Custom Availability Page**: select a time range → see free drivers and vehicles.
-   **Business Rules**: no overlapping trips per driver/vehicle.
-   **UI Customization**: modernized Filament theme (navbar color, headers, activity count in topbar).

---

## 🧪 Testing

Tests are written with **Pest**.

Run the full suite with coverage:

```bash
php artisan test --coverage --min=80
```

### What’s Covered

-   Trip scheduling rules (overlaps, invalid times).
-   Availability scopes.
-   KPI calculations.
-   Filament forms and custom pages.

**Target:** ≥ 80% coverage

---

## 🚀 Performance Notes

-   **Eager Loading**: Drivers and Vehicles are eager-loaded with trips to prevent N+1 queries.
-   **Caching**: KPI queries cached for 60 seconds to avoid heavy recalculations.
-   **Indexes**: Foreign keys and composite indexes optimize trip overlap lookups.

---

## 📂 Tech Stack

-   **Laravel 12**
-   **Filament 4** (admin panel, resources, custom pages)
-   **Pest** (testing)
-   **Tailwind** (UI styling)
-   **MySQL**

---

## 👨‍💻 Author

Built by **Mikail Hamzat** as part of the **Hypersender Filament Coding Challenge**.

---

## Contributing

Contributions welcome. Open an issue or send a PR.

---

## License

This project is licensed under the [MIT License](./LICENSE).
