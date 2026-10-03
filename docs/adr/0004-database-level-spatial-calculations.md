# ADR 0004: Database-Level Spatial Calculations vs Application Loops

## Status
Accepted

## Context
When computing distances and filtering entities in location-based applications, developers sometimes fetch all records into application memory (PHP/Laravel Collections) and compute distances using PHP formulas (such as Haversine) or external helper packages:
```php
// ANTI-PATTERN:
$drivers = Driver::all()->filter(fn ($d) => haversine($userLat, $userLng, $d->lat, $d->lng) < 5000);
```

On a small dataset (10–50 rows), this might appear viable. However, at scale (100k+ rows):
- Memory exhaustion: Hydrating 100,000 Eloquent model instances consumes several gigabytes of PHP RAM.
- CPU bottleneck: Single-threaded PHP execution iterating over 100,000 items takes hundreds of milliseconds or seconds.
- Network saturation: Transferring thousands of coordinate records from PostgreSQL to PHP over TCP generates massive network overhead.

## Decision
All spatial calculations, filtering, and sorting are executed strictly **inside PostgreSQL/PostGIS** at the database layer. PHP receives only the final paginated or limited result set (e.g., 20 records).

## Consequences

### Positive
- **Minimal Memory Footprint**: PHP hydronates only the requested records (e.g., 1 to 20 Eloquent models).
- **Sub-Millisecond Execution**: PostGIS utilizes C-level spatial routines, SIMD optimizations, and GiST bounding box filtering.
- **Zero Network Bloat**: Only relevant rows cross the network interface between the database container and application container.
- **Strict Architecture Isolation**: The application layer defines *what* is required; the database layer optimizes *how* it is fetched.

### Negative
- Requires PostGIS extension installed and running in the database environment.
- Developers must understand PostGIS SQL functions (`ST_DWithin`, `ST_Distance`, `ST_Contains`) and utilize raw Eloquent scopes when building queries.
