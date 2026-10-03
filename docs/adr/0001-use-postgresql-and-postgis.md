# ADR 0001: Use PostgreSQL and PostGIS over MySQL

## Status
Accepted

## Context
GeoDispatch requires efficient handling of spatial data, including:
- Real-time radius queries on 100k+ point coordinates.
- KNN (K-Nearest Neighbor) sorting for instantaneous dispatch.
- Polygon containment checks for dynamic service areas.
- High accuracy geodesic distance calculations accounting for the curvature of the Earth.

While MySQL supports basic spatial extensions and spatial indexes (R-Tree on MyISAM/InnoDB), its spatial capabilities have known limitations:
- Limited support for geodesic calculations on spherical coordinates (`geography` type).
- Absence of true K-Nearest Neighbor (KNN) index-assisted sorting operators like PostGIS `<->`.
- Inflexible spatial function ecosystem compared to OGC-compliant PostGIS.

## Decision
We chose **PostgreSQL** with the **PostGIS** extension (`postgis/postgis:17-3.5`) as the primary database engine.

## Consequences

### Positive
- **Advanced Spatial Functions**: Native support for `ST_DWithin`, `ST_Distance`, `ST_Contains`, `ST_MakePoint`, and `ST_SetSRID`.
- **True KNN Index Traversal**: PostgreSQL supports the `<->` distance operator on GiST indexes, ordering 100k+ records in single-digit milliseconds without sorting all rows in memory.
- **Accurate Geodesic Math**: Native `geography` type uses Great Circle and WGS 84 ellipsoidal math without manual projection transformations.
- **Standard Compliance**: PostGIS is the industry benchmark for spatial engineering and Open Geospatial Consortium (OGC) compliance.

### Negative
- Requires PostGIS extension installation and container image (`postgis/postgis`) instead of standard PostgreSQL images.
- Slightly higher resource utilization during index construction compared to flat B-Tree indexes.
