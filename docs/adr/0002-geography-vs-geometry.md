# ADR 0002: Geography vs Geometry for Spatial Entities

## Status
Accepted

## Context
PostGIS provides two primary spatial data types for representing spatial features:
1. `geometry`: Represents data on a Cartesian plane (flat 2D surface). Distances and measurements are calculated using Euclidean math in planar units (often degrees or projected coordinate meters depending on SRID).
2. `geography`: Represents data on a spherical or ellipsoidal model of the Earth (WGS 84, SRID 4326). Distances and areas are calculated natively in **meters** on the curved surface of the Earth.

The core entities of GeoDispatch are moving vehicles (`drivers`) and customers requesting service with real-world GPS coordinates (Latitude/Longitude).

## Decision
- We use the **`geography(Point, 4326)`** type for `drivers` and `customers` locations.
- We use **`geometry(Polygon, 4326)`** for `service_areas` boundaries.

## Rationale
1. **Accurate Distance Calculations**: Using `geometry` with unprojected lat/lng (SRID 4326) results in distance calculations measured in angular degrees, which distort significantly depending on latitude. `geography` guarantees distance measurements in meters.
2. **No Complex Coordinate Transformations**: Avoids the requirement to reproject coordinates into local UTM zones (e.g., EPSG:32636 for Egypt) before executing radius queries.
3. **Polygon Containment Efficiency**: Polygon boundaries in `service_areas` benefit from planar geometry algorithms (`ST_Contains`) which are fast and deterministic for city-scale boundaries.

## Consequences

### Positive
- `ST_DWithin(location, ..., 5000)` filters directly in meters without conversion constants.
- `ST_Distance(location, ...)` outputs geodesic distance directly in meters.
- Prevents geometric distortion across differing latitudes.

### Negative
- Spherical trigonometric computations are computationally heavier than 2D Euclidean math, making spatial indexing (GiST) mandatory for high-volume datasets.
