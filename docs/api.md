# GeoDispatch — API Documentation

GeoDispatch provides a RESTful API designed for high-performance spatial querying and driver dispatch operations.

**Base URL**: `http://localhost:8000/api` (or matching your host environment)

All requests and responses use `application/json` format.

---

## 1. Spatial Dispatch Endpoints

### 1.1 UC-01: Find Nearby Drivers
Retrieves available drivers within a specified radius (in meters) around a central coordinate, ordered from closest to furthest.

* **Endpoint**: `GET /api/drivers/nearby`
* **Headers**: `Accept: application/json`

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `latitude` | float | **Yes** | — | Center latitude (-90.0 to 90.0) |
| `longitude` | float | **Yes** | — | Center longitude (-180.0 to 180.0) |
| `radius` | float | No | `5000` | Search radius in meters (10 to 100,000) |
| `status` | string | No | `available` | Filter by status (`available`, `busy`, `offline`, or `all`) |
| `limit` | integer| No | `20` | Maximum results to return (1 to 100) |

#### Example Request:
```http
GET /api/drivers/nearby?latitude=30.0444&longitude=31.2357&radius=3000&limit=2
```

#### Example Response (`200 OK`):
```json
{
  "data": [
    {
      "id": 142,
      "name": "Captain Ahmed",
      "status": "available",
      "coordinates": {
        "latitude": 30.0470,
        "longitude": 31.2390
      },
      "distance": {
        "meters": 435.82,
        "kilometers": 0.44
      }
    },
    {
      "id": 9801,
      "name": "Captain Mahmoud",
      "status": "available",
      "coordinates": {
        "latitude": 30.0520,
        "longitude": 31.2420
      },
      "distance": {
        "meters": 1042.15,
        "kilometers": 1.04
      }
    }
  ]
}
```

---

### 1.2 UC-02: Find Nearest Driver
Finds the single nearest available driver using the PostGIS KNN proximity operator (`<->`).

* **Endpoint**: `GET /api/drivers/nearest`
* **Headers**: `Accept: application/json`

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `latitude` | float | **Yes** | — | Target latitude (-90.0 to 90.0) |
| `longitude` | float | **Yes** | — | Target longitude (-180.0 to 180.0) |
| `status` | string | No | `available` | Filter by status (`available`, `busy`, `offline`, or `all`) |

#### Example Request:
```http
GET /api/drivers/nearest?latitude=30.0444&longitude=31.2357
```

#### Example Response (`200 OK`):
```json
{
  "data": {
    "id": 142,
    "name": "Captain Ahmed",
    "status": "available",
    "coordinates": {
      "latitude": 30.0470,
      "longitude": 31.2390
    },
    "distance": {
      "meters": 435.82,
      "kilometers": 0.44
    }
  }
}
```

#### Not Found Response (`404 Not Found`):
```json
{
  "message": "No available drivers found."
}
```

---

### 1.3 UC-03: Check Service Area Containment
Checks whether a given coordinate point falls inside any registered geographic service polygon using `ST_Contains`.

* **Endpoint**: `GET /api/service-areas/check`
* **Headers**: `Accept: application/json`

#### Query Parameters:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `latitude` | float | **Yes** | Point latitude (-90.0 to 90.0) |
| `longitude` | float | **Yes** | Point longitude (-180.0 to 180.0) |

#### Example Request:
```http
GET /api/service-areas/check?latitude=30.0500&longitude=31.2500
```

#### Example Response — Inside Service Area (`200 OK`):
```json
{
  "serviceable": true,
  "service_area": {
    "id": 1,
    "name": "Cairo Center"
  }
}
```

#### Example Response — Outside Service Area (`404 Not Found`):
```json
{
  "serviceable": false,
  "message": "No service area found for the given coordinates."
}
```

---

## 2. Resource Management Endpoints

### 2.1 Drivers
* `GET /api/drivers`: Paginated list of drivers with latitude and longitude coordinates.
* `GET /api/drivers/{id}`: Detailed information of a specific driver.

### 2.2 Service Areas
* `GET /api/service-areas`: Paginated list of registered service areas.

### 2.3 Customers
* `GET /api/customers`: Paginated list of customer locations.

---

## 3. Error Responses & Validation Handling

### Validation Error (`422 Unprocessable Content`):
Returned when required coordinates are missing, out of range, or invalid:
```json
{
  "message": "The latitude field is required. (and 1 more error)",
  "errors": {
    "latitude": [
      "The latitude field is required."
    ],
    "longitude": [
      "The longitude field is required."
    ]
  }
}
```
