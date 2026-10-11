# Operator Dashboard API — Manual Testing Guide

This guide walks through manually testing the operator dashboard endpoints using the
interactive API documentation (OpenAPI / "Swagger"-style UI). It assumes no prior knowledge of
the project.

Endpoints covered:

| Method | Endpoint                 | Who can call it      | Purpose                                              |
|--------|--------------------------|----------------------|------------------------------------------------------|
| GET    | `/api/dashboard/summary` | OPERATOR, ADMIN      | Total report count plus a count for every status     |
| GET    | `/api/reports`           | OPERATOR, ADMIN      | Paginated report list with filters and sorting       |

Both require a Sanctum bearer token. CITIZEN accounts receive `403 Forbidden`; requests
without a token receive `401 Unauthorized`.

---

## 1. Prerequisites

1. Start the stack (from the project root):
   ```bash
   docker compose up -d
   ```
2. Apply migrations:
   ```bash
   docker compose exec laravel.test php artisan migrate
   ```
3. Load the demo data (safe to re-run; it never runs in production):
   ```bash
   docker compose exec laravel.test php artisan db:seed --class=CivicPulseDemoSeeder
   ```
4. Make sure `.env` has `APP_ENV=local`. The API docs UI is only served in the `local`
   environment.

### Demo accounts

All demo accounts use the password **`DemoOnly123!`**.

| Email                         | Role     |
|-------------------------------|----------|
| `admin1@civicpulse.test`      | ADMIN    |
| `operator1@civicpulse.test`   | OPERATOR |
| `operator2@civicpulse.test`   | OPERATOR |
| `operator3@civicpulse.test`   | OPERATOR |
| `citizen1@civicpulse.test` … `citizen5@civicpulse.test` | CITIZEN |

### Demo data (fresh database)

The seeder creates 30 reports, all with today's `created_at`:

| Status        | Count | Assigned to `operator1` |
|---------------|-------|--------------------------|
| `OPEN`        | 8     | 0                        |
| `ASSIGNED`    | 8     | 3                        |
| `IN_PROGRESS` | 7     | 2                        |
| `RESOLVED`    | 7     | 2                        |
| **Total**     | **30**| **7**                    |

If you have created additional reports by hand, your numbers will be higher. That is fine: check
that the numbers agree with each other (for example, the summary counts match the list totals),
not that they match this table exactly.

---

## 2. Open the API documentation

Open **http://localhost:8000/docs/api** in a browser.

The project uses [Scramble](https://scramble.dedoc.co) to generate an OpenAPI 3 specification
from the code. It is rendered with Stoplight Elements, which works like Swagger UI: endpoints
are listed on the left, and each endpoint has a **Try It** panel on the right for sending real
requests. The raw spec is available at http://localhost:8000/docs/api.json if you prefer to
import it into Swagger UI, Postman or Insomnia.

The new endpoints appear under:

- **Dashboard → Report totals for the operator dashboard** (`GET /dashboard/summary`)
- **Report → index** (`GET /reports`)

Paths in the UI do not show the `/api` prefix because it is part of the server URL
(`http://localhost:8000/api`).

---

## 3. Authenticate

1. In the sidebar, open **POST /login**.
2. In **Try It → Body**, enter:
   ```json
   {
     "email": "operator1@civicpulse.test",
     "password": "DemoOnly123!"
   }
   ```
3. Click **Send API Request**. Expected `200 OK`:
   ```json
   {
     "token": "5|q8Xn...redacted...",
     "token_type": "Bearer",
     "user": {
       "id": 12,
       "name": "Operator 1",
       "email": "operator1@civicpulse.test",
       "role": "OPERATOR",
       "created_at": "2026-10-11T18:13:23.000000Z"
     }
   }
   ```
4. Copy the `token` value. Also note `user.id`, which you need for the `assigned_to=<id>` test.
5. Open any protected endpoint (e.g. **GET /dashboard/summary**). In the **Try It** panel, find
   the **Auth** section and paste the token into the **Bearer token** field. Paste only the
   token; the UI adds the `Bearer ` prefix itself. The token is reused for the other endpoints
   on the page.

Repeat steps 1–4 with `admin1@…` and `citizen1@…` when a test below calls for those roles.
Replace the token in the Auth field each time you switch users.

> **curl equivalent** (always send `Accept: application/json`; see §7):
> ```bash
> curl -s -X POST http://localhost:8000/api/login \
>   -H 'Accept: application/json' \
>   -d email=operator1@civicpulse.test -d 'password=DemoOnly123!'
> ```

---

## 4. `GET /api/dashboard/summary`

Returns the total number of reports and a count for each status. Every status is always
present, with `0` when no report has that status. It takes no parameters.

### 4.1 Operator, happy path

Logged in as `operator1`, send the request. Expected `200 OK`:

```json
{
  "data": {
    "total": 30,
    "by_status": {
      "OPEN": 8,
      "ASSIGNED": 8,
      "IN_PROGRESS": 7,
      "RESOLVED": 7
    }
  }
}
```

Check that `total` equals the sum of the four `by_status` values.

### 4.2 Admin

Log in as `admin1@civicpulse.test` and repeat. Expected: the same `200` response.

### 4.3 Negative scenarios

| Scenario                         | How                                          | Expected                                      |
|----------------------------------|----------------------------------------------|-----------------------------------------------|
| No token                         | Clear the Bearer token field, send           | `401` `{"message": "Unauthenticated."}`        |
| Invalid / revoked token          | Bearer token `invalid`                        | `401` `{"message": "Unauthenticated."}`        |
| Citizen                          | Log in as `citizen1@…`, use that token        | `403` `{"message": "This action is unauthorized."}` |

With `APP_DEBUG=true` (the local default), error responses also include `exception`/`trace`
fields. That is expected locally.

---

## 5. `GET /api/reports`

Returns a paginated list of reports, newest first by default. Each item includes its
`category` and `assigned_operator` (or `null` when unassigned). These are loaded with eager
loading, so the number of database queries does not grow with the number of reports.

### 5.1 Query parameters

All parameters are optional and can be combined. Values are validated; an invalid value returns
`422` with an `errors` object (see §5.4).

| Parameter      | Type / allowed values                                       | Default      | Meaning |
|----------------|-------------------------------------------------------------|--------------|---------|
| `status`       | `OPEN`, `ASSIGNED`, `IN_PROGRESS`, `RESOLVED`               | —            | Only reports with this status |
| `category_id`  | positive integer                                            | —            | Only reports in this category (unknown id → empty list, not an error) |
| `assigned_to`  | `me` or a positive integer user id                          | —            | `me` = reports assigned to the logged-in user; a number = reports assigned to that user |
| `created_from` | date `YYYY-MM-DD`                                           | —            | Reports created on or after this day (00:00:00 UTC) |
| `created_to`   | date `YYYY-MM-DD`, must be ≥ `created_from`                 | —            | Reports created on or before this day (inclusive, up to 23:59:59 UTC) |
| `sort`         | `created_at`, `updated_at`, `assigned_at`, `resolved_at`, `status` | `created_at` | Sort column. Empty values (e.g. `assigned_at` for OPEN reports) always sort last |
| `direction`    | `asc`, `desc`                                               | `desc`       | Sort direction |
| `per_page`     | integer 1–100                                               | `15`         | Page size |
| `page`         | integer ≥ 1                                                 | `1`          | Page number |

Dates are interpreted in UTC (the application timezone).

In the docs UI, each parameter has its own input under **Try It → Parameters**. Leave a field
empty to omit that parameter.

### 5.2 Response shape

```json
{
  "data": [
    {
      "id": "01a12c2b-6b62-7336-847d-8cc97d1e3b9c",
      "category_id": 11,
      "category": {
        "id": 11,
        "name": "Water Supply",
        "description": "Water leaks and supply problems"
      },
      "description": "Large pothole near the main road",
      "location": "0101000020E6100000ECC039234A1756408AB0E1E995923640",
      "tracking_number": "CP-DEMO-0019",
      "reporter_email": "citizen4@civicpulse.test",
      "created_at": "2026-10-11T18:13:25.000000Z",
      "updated_at": "2026-10-11T18:13:25.000000Z",
      "status": "IN_PROGRESS",
      "assigned_operator_id": 12,
      "assigned_operator": {
        "id": 12,
        "name": "Operator 1",
        "email": "operator1@civicpulse.test",
        "role": "OPERATOR",
        "created_at": "2026-10-11T18:13:23.000000Z"
      },
      "assigned_at": "2026-10-11T18:13:25.000000Z",
      "started_at": "2026-10-11T18:13:25.000000Z",
      "resolved_at": null,
      "version": 2
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/reports?assigned_to=me&status=IN_PROGRESS&per_page=1&page=1",
    "last":  "http://localhost:8000/api/reports?assigned_to=me&status=IN_PROGRESS&per_page=1&page=2",
    "prev": null,
    "next":  "http://localhost:8000/api/reports?assigned_to=me&status=IN_PROGRESS&per_page=1&page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 2,
    "links": [ "…page link objects…" ],
    "path": "http://localhost:8000/api/reports",
    "per_page": 1,
    "to": 1,
    "total": 2
  }
}
```

Key fields to check:

- `meta.total`: number of reports matching the filters, across all pages.
- `meta.per_page`, `meta.current_page`, `meta.last_page`: pagination state.
- `links.next` / `links.prev`: these keep your filters in the query string, so you can follow
  them directly.

### 5.3 Happy-path scenarios

Run each one logged in as `operator1` unless stated otherwise. Expected values assume a fresh
demo database (§1).

| # | Parameters | Expected |
|---|------------|----------|
| 1 | *(none)* | `200`; 15 items; `meta.total` = 30; `meta.last_page` = 2; items newest first |
| 2 | `per_page=10`, `page=3` | `200`; 10 items; `meta.current_page` = 3; `links.next` = `null` |
| 3 | `status=OPEN` | `200`; `meta.total` = 8; every item has `"status": "OPEN"` and `"assigned_operator": null` |
| 4 | `status=RESOLVED` | `200`; `meta.total` = 7; every item has a non-null `resolved_at` |
| 5 | `assigned_to=me` | `200`; `meta.total` = 7; every `assigned_operator_id` equals your `user.id` |
| 6 | `assigned_to=me`, `status=IN_PROGRESS` | `200`; `meta.total` = 2 |
| 7 | `assigned_to=<operator2's id>` | `200`; every `assigned_operator_id` equals that id. Get the id by logging in as `operator2` or from an item's `assigned_operator.id` |
| 8 | `category_id=<id>` | `200`; every item's `category.id` equals `<id>`. Take an id from any item's `category` |
| 9 | `category_id=999999` | `200`; empty `data`; `meta.total` = 0 |
| 10 | `created_from=<today>`, `created_to=<today>` | `200`; `meta.total` = 30 (all demo reports were created today, UTC) |
| 11 | `created_to=<yesterday>` | `200`; `meta.total` = 0 |
| 12 | `sort=created_at`, `direction=asc` | `200`; oldest first (compare `created_at` of first and last items) |
| 13 | `sort=assigned_at`, `direction=desc` | `200`; assigned reports first; OPEN reports (`assigned_at: null`) at the end of the last page |
| 14 | `status=OPEN`, `per_page=3` | `200`; `links.next` contains `status=OPEN&per_page=3&page=2` |
| 15 | Log in as `admin1`, `assigned_to=me` | `200`; `meta.total` = 0 (reports are assigned to operators, never to admins) |
| 16 | Log in as `admin1`, *(none)* | `200`; same results as #1 |

Cross-check: for each status, `GET /api/reports?status=<STATUS>` → `meta.total` should equal
`by_status.<STATUS>` from `GET /api/dashboard/summary`.

### 5.4 Negative scenarios

Validation errors return `422 Unprocessable Content` in this format:

```json
{
  "message": "The selected status is invalid.",
  "errors": {
    "status": ["The selected status is invalid."]
  }
}
```

| # | Request | Expected status | Error field / message |
|---|---------|-----------------|-----------------------|
| N1 | No token | `401` | `{"message": "Unauthenticated."}` |
| N2 | Citizen token | `403` | `This action is unauthorized.` |
| N3 | Citizen token + `status=NOT_A_STATUS` | `403` | Authorization is checked **before** validation, so citizens never see validation details |
| N4 | `status=CLOSED` | `422` | `status`: The selected status is invalid. |
| N5 | `category_id=abc` | `422` | `category_id`: must be an integer |
| N6 | `assigned_to=bob` | `422` | `assigned_to`: The assigned to field must be "me" or a user id. |
| N7 | `assigned_to=0` or `assigned_to=-1` | `422` | `assigned_to` |
| N8 | `created_from=11-10-2026` | `422` | `created_from`: must match the format Y-m-d |
| N9 | `created_from=2026-10-10`, `created_to=2026-10-01` | `422` | `created_to`: must be a date after or equal to created from |
| N10 | `sort=reporter_email` | `422` | `sort`: The selected sort is invalid. |
| N11 | `direction=sideways` | `422` | `direction`: The selected direction is invalid. |
| N12 | `per_page=101` / `per_page=0` | `422` | `per_page`: must not be greater than 100 / at least 1 |
| N13 | `page=0` | `422` | `page`: must be at least 1 |

When several parameters are invalid, every error is returned at once. Example:
`status=CLOSED&assigned_to=bob&created_from=2026-10-10&created_to=2026-10-01&sort=email&per_page=500`
returns 422 with errors for `status`, `assigned_to`, `created_to`, `sort` and `per_page`.

---

## 6. Optional: confirm the dashboard reflects workflow changes

This checks that the summary and filters update live as reports move through the workflow.
These existing endpoints are unchanged by this feature.

1. As `operator1`, `GET /dashboard/summary` and note `OPEN` and `ASSIGNED`.
2. `GET /reports?status=OPEN&per_page=1` and copy the report `id`.
3. `POST /reports/{id}/assign` with body `{"operator_id": <your user.id>, "reason": "Manual test"}` → `200`.
4. `GET /dashboard/summary` again. Expected: `OPEN` is one lower, `ASSIGNED` is one higher, and
   `total` is unchanged.
5. `GET /reports?assigned_to=me&status=ASSIGNED` and confirm the report now appears.

This changes your local data. Re-running the seeder will not undo it; run
`php artisan migrate:fresh` and then seed again to reset.

---

## 7. Troubleshooting

| Symptom | Cause / fix |
|---------|-------------|
| `/docs/api` returns 403 | `APP_ENV` is not `local`. Scramble restricts the docs UI outside local. |
| Endpoint missing from the docs UI | Clear the cached spec: `docker compose exec laravel.test php artisan scramble:clear` (only relevant if `scramble:cache` was run). |
| `500` with "Route [login] not defined" when unauthenticated | The request was sent without `Accept: application/json`. Laravel then tries to redirect to a web login page that doesn't exist. The docs UI sends this header; for curl/Postman, add it yourself. This existing behavior affects every authenticated route. |
| Always `401` despite logging in | The token was pasted with a `Bearer ` prefix, or it was revoked by `POST /logout`. Log in again and paste only the token. |
| Counts differ from §1 | Extra reports exist locally. Compare summary and list totals with each other instead (§5.3 cross-check). |

---

## 8. Automated tests

These scenarios are also covered by PHPUnit feature tests in
`tests/Feature/OperatorDashboardApiTest.php`:

```bash
docker compose exec laravel.test php artisan test --filter=OperatorDashboardApiTest
```
