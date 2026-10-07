# GeoDispatch — High-Performance Spatial Dispatch Backend

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?style=flat&logo=postgresql)](https://www.postgresql.org/)
[![PostGIS](https://img.shields.io/badge/PostGIS-3.5-006699?style=flat)](https://postgis.net/)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=flat&logo=docker)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Tests-21%20Passed-44CC11?style=flat)](./tests)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

<p align="center">
  <a href="./assets/GeoDispatch_routing_system_anima…_20261007155154.mp4">
    <img src="./assets/PostGIS Neon City Dashboard.png" alt="GeoDispatch Preview — Click to watch demo" width="100%"/>
  </a>
  <br/>
  <sub>▶️ Click the image to watch the demo video</sub>
</p>

GeoDispatch is a production-grade REST API backend built to demonstrate **high-performance geographic data handling, spatial query optimization, and real-time resource dispatching** at scale using **Laravel 11**, **PostgreSQL 17**, and **PostGIS 3.5**.

---

## 🌟 Key Features

- **🚀 Sub-Millisecond Spatial Ordering (KNN)**: Instant retrieval of the nearest available driver using the PostGIS KNN distance operator (`<->`) backed by GiST indexing on 100k+ records.
- **📍 Radius Proximity Search**: Efficient spherical distance filtering (`ST_DWithin`) and geodesic distance calculations (`ST_Distance`) on ellipsoidal earth coordinates (`geography` type).
- **🗺️ Polygon Boundary Containment**: Real-time determination of whether customer coordinates reside within defined operational service areas using `ST_Contains`.
- **⚡ Zero PHP Distance Overhead**: All spatial math and filtering execute strictly inside the PostgreSQL/PostGIS engine in optimized C routines.
- **🧪 100% Automated Test Coverage**: Full suite of 21 Unit and Feature tests with isolated transactional test database support (`RefreshDatabase`).
- **📊 Built-in Benchmarking Tool**: CLI command to measure and compare spatial queries with and without spatial GiST indexing.

---

## 🏗️ Architecture & Technical Stack

```
[Client / Mobile App]
        │
        ▼ (HTTP REST / JSON)
[Routing & Validation Layer] ── FormRequests (Coordinate bounds & status validation)
        │
        ▼
[Thin Controllers] ───────────── DriverController & ServiceAreaController
        │
        ▼
[Domain Actions] ─────────────── Single Responsibility Actions (FindNearbyDriversAction, etc.)
        │
        ▼
[Eloquent Models & Scopes] ───── Custom PostGIS Spatial Scopes (ST_DWithin, ST_Distance, KNN)
        │
        ▼
[PostgreSQL 17 + PostGIS 3.5] ── GiST Spatial Index on geography(Point, 4326)
        │
        ▼
[API Resources] ──────────────── Strict JSON serialization with lat/lng & distances
```

Detailed architectural diagrams and flow can be found in [docs/architecture.md](docs/architecture.md).

---

## 📈 Performance Benchmarks (100,000+ Drivers)

GeoDispatch includes a benchmarking CLI command:
```bash
php artisan geo:benchmark --iterations=5
```

### Benchmark Results on Cairo Urban Coordinates (Lat 30.0444, Lng 31.2357):

| Use Case | Query Method | Without GiST Index | With GiST Index | Performance Gain |
| :--- | :--- | :---: | :---: | :---: |
| **UC-01: Radius Search (5km)** | `ST_DWithin` + KNN Order | ~240.5 ms | **2.6 ms** | **~92x Faster** |
| **UC-02: Nearest Driver** | KNN Operator (`<->`) | ~185.2 ms | **0.8 ms** | **~230x Faster** |

> **Key takeaway**: PostGIS GiST index transforms spatial queries from expensive sequential table scans into fast B-Tree-like R-Tree bounding-box traversals.

---

## 🚀 Quick Start (Docker Environment)

### 1. Prerequisites
- Docker & Docker Compose
- PHP 8.2+ with `pdo_pgsql` extension enabled (for local CLI/artisan test execution)
- Composer

### 2. Clone and Setup Environment
```bash
git clone https://github.com/AhmedAbdEllatif7/GeoDispatch-Nearest-Driver.git
cd GeoDispatch-Nearest-Driver

cp .env.example .env
composer install
```

### 3. Start Docker Infrastructure
```bash
docker compose up -d
```
This spins up:
- **`db`**: PostgreSQL 17 with PostGIS 3.5 on port `5432`
- **`adminer`**: Database GUI management tool at `http://localhost:8080`

### 4. Run Migrations & Seed Dataset
```bash
# Run migrations on local/Docker database
php artisan migrate

# Seed 100,000 realistic driver coordinates around Cairo metropolitan area
php artisan db:seed --class=LargeScaleDriverSeeder
```

---

## 🧪 Running Automated Tests

GeoDispatch maintains an isolated test database (`geodispatch_test`) to ensure tests never touch development or production data.

```bash
# Execute the full Pest/PHPUnit test suite
php artisan test --env=testing
```

Expected output:
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\CheckServiceAreaActionTest
   PASS  Tests\Feature\CheckServiceAreaApiTest
   PASS  Tests\Feature\ExampleTest
   PASS  Tests\Feature\NearbyDriversTest
   PASS  Tests\Feature\NearestDriverTest

  Tests:    21 passed (80 assertions)
  Duration: ~15s
```

---

## 📡 API Overview

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/drivers/nearby` | Find drivers within radius (in meters) around coordinates |
| `GET` | `/api/drivers/nearest` | Find the single nearest available driver (KNN) |
| `GET` | `/api/service-areas/check` | Check if coordinates fall inside any service polygon |
| `GET` | `/api/drivers` | Paginated listing of drivers with coordinates |
| `GET` | `/api/service-areas` | List of defined service areas |

For full request/response schemas, validation rules, and examples, refer to [docs/api.md](docs/api.md).

---

## 📚 Documentation & ADRs

- 📐 **[Architecture Overview](docs/architecture.md)** — Architectural design, layers, models, and spatial scopes.
- 📖 **[API Documentation](docs/api.md)** — Detailed endpoint reference with request/response payloads.
- 📋 **[ADR 0001: PostgreSQL & PostGIS over MySQL](docs/adr/0001-use-postgresql-and-postgis.md)**
- 📋 **[ADR 0002: Geography vs Geometry Data Types](docs/adr/0002-geography-vs-geometry.md)**
- 📋 **[ADR 0003: GiST Spatial Indexing and KNN Optimization](docs/adr/0003-gist-spatial-indexing-and-knn.md)**
- 📋 **[ADR 0004: Database-Level Spatial Operations](docs/adr/0004-database-level-spatial-calculations.md)**

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
