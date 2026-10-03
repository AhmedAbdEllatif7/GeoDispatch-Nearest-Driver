# GeoDispatch — Architecture Documentation

## 1. System Overview

GeoDispatch is a high-performance spatial dispatch backend built with **Laravel 11**, **PostgreSQL 17**, and **PostGIS 3.5**.
The system is designed to efficiently handle massive spatial coordinate datasets (100,000+ driver points), execute real-time proximity searches, determine spatial containment within complex polygon service areas, and maintain sub-millisecond query performance using spatial indexing.

---

## 2. Layered Architecture

GeoDispatch follows a strict single-responsibility, action-based layered architecture:

```
[HTTP Request]
       │
       ▼
[Routing Layer] ───────── routes/api.php
       │
       ▼
[Validation Layer] ────── FormRequests (Coordinate & Radius validation)
       │
       ▼
[Controller Layer] ────── Thin Controllers (DriverController, ServiceAreaController)
       │
       ▼
[Action Layer] ────────── Dedicated Domain Actions (FindNearbyDriversAction, etc.)
       │
       ▼
[Domain / Model Layer] ── Eloquent Models & PostGIS Spatial Scopes (Driver, ServiceArea)
       │
       ▼
[Database Engine] ─────── PostgreSQL 17 + PostGIS 3.5 + GiST Spatial Indexes
       │
       ▼
[Transformation Layer] ── Laravel API Resources (DriverResource, ServiceAreaResource)
       │
       ▼
[HTTP JSON Response]
```

### 2.1 Routing Layer (`routes/api.php`)
Exposes RESTful endpoints versioned and mapped to specific controller actions:
- `GET /api/drivers/nearby`: Radius-based spatial search.
- `GET /api/drivers/nearest`: K-Nearest Neighbor (KNN) proximity search.
- `GET /api/service-areas/check`: Spatial polygon containment check.
- Standard CRUD endpoints for resource inspection.

### 2.2 Validation Layer (`app/Http/Requests`)
Encapsulates all incoming input validation rules:
- Coordinate validity: `latitude` must be between -90.0 and +90.0 degrees; `longitude` must be between -180.0 and +180.0 degrees.
- Distance boundaries: `radius` bounded between 10 meters and 100,000 meters (100 km).
- Status validation: Enum-backed validation using `DriverStatus` (`available`, `busy`, `offline`, or `all`).

### 2.3 Controller Layer (`app/Http/Controllers`)
Controllers remain strictly thin. They do not contain business logic or raw queries. They:
1. Authorize and accept validated request inputs.
2. Delegate business logic execution to dedicated Action classes.
3. Pass results to API Resources for serialization.

### 2.4 Action Layer (`app/Actions`)
Each core business use-case is encapsulated inside a Single Action:
- `FindNearbyDriversAction`: Executes radius filtering and distance calculation.
- `FindNearestDriverAction`: Executes KNN proximity ordering to fetch the nearest available driver.
- `CheckServiceAreaAction`: Determines whether a given coordinate falls within any defined service polygon using spatial containment.

### 2.5 Domain & Model Layer (`app/Models`)
Spatial queries are encapsulated into reusable Eloquent Scopes:
- `scopeWithCoordinates()`: Extracts scalar `latitude` and `longitude` via `ST_Y()` and `ST_X()`.
- `scopeWithinDistanceTo()`: Uses PostGIS `ST_DWithin` on the `geography` type to leverage GiST indexes.
- `scopeWithDistanceTo()`: Calculates geodesic spherical distance in meters via `ST_Distance()`.
- `scopeOrderByDistanceTo()`: Utilizes the PostGIS KNN distance operator (`<->`) for index-assisted nearest neighbor sorting.

---

## 3. Database & Spatial Infrastructure

### 3.1 Spatial Data Types
- **Point Entities (`drivers`, `customers`)**: Stored using `geography(Point, 4326)` (WGS 84 ellipsoid). Longitude and Latitude are stored as true earth coordinates.
- **Polygonal Entities (`service_areas`)**: Stored using `geometry(Polygon, 4326)` for boundary containment checks.

### 3.2 Indexing Strategy
- **GiST (Generalized Search Tree) Index**: Created on spatial columns (`drivers.location`, `service_areas.boundary`).
  - Enables index scans for bounding-box and distance checks (`ST_DWithin`).
  - Supports the `<->` KNN operator for instant nearest-neighbor queries without a full table scan.
- **B-Tree Index**: Created on scalar filtering columns (`drivers.status`) to allow composite evaluation when combined with spatial filters.

---

## 4. Environment & Containerization

The system runs on Docker Compose with multi-container orchestration:
- **`geodispatch-app`**: PHP 8.2+ / Laravel application container.
- **`db`**: `postgis/postgis:17-3.5` container with persistent volume storage (`pgdata`).
- **`adminer`**: Web-based database management interface mapped to port 8080.
- **Test Isolation**: A dedicated `geodispatch_test` database is initialized in the same PostgreSQL cluster for automated testing with transactions rollback (`RefreshDatabase`).
