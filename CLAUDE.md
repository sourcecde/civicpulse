# CLAUDE.md — CivicPulse

## 1. Project Overview

CivicPulse is a civic issue reporting and urban operations platform.

Citizens can report local problems such as:
- potholes
- waterlogging
- garbage
- broken streetlights
- drainage/sewer issues
- water leakage
- damaged roads/footpaths
- other civic issues

Each report should support:
- photo evidence
- GPS/location
- category
- short description
- status tracking
- public visibility on a map
- community confirmation ("I'm affected too")

The operator side allows an organization to:
- review issues
- assign issues to departments or teams
- set priority
- track SLA
- update status
- add resolution evidence
- analyze issue hotspots and operational performance

Kolkata is the first pilot market, especially for monsoon waterlogging, but the product must NOT be hard-coded for Kolkata.

Long-term goal:
CivicPulse should evolve into a configurable civic operations SaaS platform for municipalities, townships, campuses, industrial parks, housing communities, and other operators.

---

## 2. Product Principles

1. Keep the citizen reporting flow extremely simple.
2. A citizen should be able to create a report in roughly 20–30 seconds.
3. Do not falsely imply official authority involvement.
4. "Authority Acknowledged" must only be used when an authorized operator has actually acknowledged an issue.
5. The live civic map is a core feature, not an optional extra.
6. Build the MVP first. Avoid premature complexity.
7. Prefer clear domain boundaries and maintainable Laravel code over clever abstractions.
8. Every important workflow should be testable.
9. Avoid introducing infrastructure unless a real requirement justifies it.
10. Design for future multi-tenancy, but do not over-engineer full multi-tenancy in the first implementation.

---

## 3. MVP Scope

### Citizen Features

- Register / login
- Create civic issue
- Upload one or more photos
- Capture or select location
- Select issue category
- Add short description
- View submitted issues
- View issue status history
- View civic issues on a map
- View issue details
- Confirm "I'm affected too"
- Detect nearby possible duplicate issues before creating a new one

### Operator Features

- Operator login
- Issue dashboard
- Filter by:
  - status
  - category
  - priority
  - area / ward
  - date
  - department
- View issue details
- Acknowledge issue
- Set priority
- Assign department/team
- Update status
- Add internal/operator comments
- Add resolution note
- Add resolution photo
- View simple operational analytics

### Initial Status Workflow

Use a controlled state model:

- REPORTED
- COMMUNITY_VERIFIED
- FORWARDED
- AUTHORITY_ACKNOWLEDGED
- ASSIGNED
- WORK_IN_PROGRESS
- RESOLVED
- RESOLUTION_VERIFIED

Transitions must be validated in backend logic.

Do not allow arbitrary status changes.

---

## 4. MVP Non-Goals

Do NOT add these unless explicitly requested:

- microservices
- Kafka
- Redis
- Elasticsearch
- AI/LLM features
- native mobile apps
- Kubernetes
- event sourcing
- CQRS
- blockchain
- unnecessary repository abstractions
- complex workflow engines

These may be considered later when there is a clear product need.

---

## 5. Technology Stack

### Backend
- PHP 8.x
- Laravel
- Laravel Sanctum for API authentication
- Eloquent ORM
- Form Requests
- API Resources
- Policies / Gates
- Events / Listeners only when useful
- Queues only when a real asynchronous need exists
- PostgreSQL
- PostGIS
- Laravel migrations
- Pest or PHPUnit

### Frontend
- React
- TypeScript
- Vite
- React Router
- lightweight API client approach
- MapLibre or Leaflet for maps
- OpenStreetMap-compatible map tiles

### Infrastructure
- Docker
- docker compose
- PostgreSQL + PostGIS container
- S3-compatible object storage for images where appropriate

Always write `docker compose`, never `docker-compose`.

---

## 6. Architecture

Start as a modular monolith.

Use Laravel conventions first.

Suggested application structure:

app/
├── Actions/
│   └── Issues/
├── Enums/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Services/
└── Support/

Possible domain areas:
- identity
- issues
- locations
- media
- departments
- assignments
- workflow
- notifications
- analytics

Do not split these into separate deployable services in the MVP.

Do not create abstract "BaseService", "BaseRepository", or generic repository layers unless there is a concrete reason.

Eloquent is the default persistence abstraction.

Controllers should be thin.

Use Actions or Services for meaningful business workflows.

---

## 7. Core Domain Model

Likely initial entities:

### User
Fields:
- id
- name
- email
- password
- role
- created_at
- updated_at

Possible roles:
- CITIZEN
- OPERATOR
- ADMIN

### CivicIssue
Fields:
- id
- public_reference
- title or short_summary
- description
- category
- status
- priority
- latitude
- longitude
- location/geography field if used for PostGIS
- address_text
- reporter_id
- department_id nullable
- assigned_to_id nullable
- created_at
- updated_at
- resolved_at nullable

### IssuePhoto
Fields:
- id
- civic_issue_id
- storage_disk
- storage_path
- type
- created_at

Types:
- REPORT
- RESOLUTION

### IssueStatusHistory
Fields:
- id
- civic_issue_id
- previous_status nullable
- new_status
- changed_by
- comment nullable
- created_at

### Department
Fields:
- id
- name
- active
- created_at
- updated_at

### IssueConfirmation
Fields:
- id
- civic_issue_id
- user_id
- created_at

Add a unique constraint so a user cannot confirm the same issue multiple times.

### Future-compatible location hierarchy
Keep the model compatible with:
- tenant
- city
- zone
- ward
- department mapping
- category configuration
- SLA rules
- branding

Do not implement full SaaS multi-tenancy unless specifically requested.

---

## 8. Enums

Prefer PHP backed enums for stable domain values.

Examples:
- IssueStatus
- IssuePriority
- IssueCategory
- UserRole
- IssuePhotoType

Do not scatter magic status/category strings throughout the codebase.

---

## 9. Geospatial Requirements

Use PostgreSQL + PostGIS.

Primary MVP geospatial use cases:
- display nearby issues
- detect possible duplicates within a configurable radius
- filter issues by map viewport
- support future ward / zone boundaries

Do not implement duplicate detection using only text addresses.

Prefer coordinates and distance-based queries.

Before adding a third-party Laravel geospatial package:
1. check whether native PostgreSQL/PostGIS queries are sufficient
2. avoid adding a package purely for convenience if it adds unnecessary abstraction

If raw geospatial SQL is required:
- isolate it clearly
- parameterize all inputs
- cover it with tests

---

## 10. API Design

Use REST for the MVP.

Example endpoints:

### Authentication
- POST /api/register
- POST /api/login
- POST /api/logout
- GET /api/me

### Issues
- POST /api/issues
- GET /api/issues
- GET /api/issues/{issue}
- GET /api/issues/nearby
- GET /api/issues/{issue}/history

### Confirmations
- POST /api/issues/{issue}/confirmations
- DELETE /api/issues/{issue}/confirmations

### Operator actions
- PATCH /api/issues/{issue}/status
- PATCH /api/issues/{issue}/priority
- PATCH /api/issues/{issue}/assignment

### Media
- POST /api/issues/{issue}/photos

Guidelines:
- use Form Request validation
- use API Resources for responses
- never return Eloquent models directly as public API contracts
- use route model binding where appropriate
- provide consistent API error responses
- use correct HTTP status codes
- paginate collection endpoints
- avoid leaking internal-only fields
- explicitly authorize every operator-only action

---

## 11. Authentication and Authorization

Use Laravel Sanctum for first-party API authentication unless a stronger requirement appears.

Authorization rules:
- citizens can create and view allowed/public issues
- citizens can manage only their own account-specific actions
- operators can perform workflow actions only when authorized
- admins can manage operator-level configuration

Use Policies where appropriate.

Never trust frontend role checks.

All permissions must be enforced server-side.

---

## 12. Security Rules

- Passwords must never be stored in plain text.
- Use Laravel's standard password hashing.
- Validate all file uploads.
- Restrict supported MIME types.
- Restrict file size.
- Generate safe storage paths.
- Do not expose local filesystem paths.
- Do not expose private citizen details in public issue responses.
- Rate-limit abuse-prone endpoints where appropriate.
- Validate latitude/longitude ranges.
- Audit important operator workflow changes.
- Prevent mass-assignment vulnerabilities.
- Never put secrets in the repository.
- Use environment variables for credentials and secrets.

---

## 13. Database and Migration Rules

Use Laravel migrations for all schema changes.

Do not manually change production schema outside migrations.

Prefer:
- foreign keys
- explicit nullable/non-nullable decisions
- unique constraints
- indexes based on actual queries
- timestamps
- clear naming

Think carefully before using cascading deletes.

Public issue references should be separate from internal numeric/UUID IDs when useful.

Example:
KOL-000142

But do not hard-code KOL if the eventual model should support other cities.

Prefer a configurable public-reference strategy.

---

## 14. Status Transition Rules

Status changes are domain behavior, not generic CRUD.

Create one clear place that decides whether a transition is allowed.

Example concept:

REPORTED
  -> COMMUNITY_VERIFIED
  -> FORWARDED
  -> AUTHORITY_ACKNOWLEDGED
  -> ASSIGNED
  -> WORK_IN_PROGRESS
  -> RESOLVED
  -> RESOLUTION_VERIFIED

Not every deployment must necessarily use every status.

Invalid transitions should return a domain/validation error.

Every successful status transition must create an IssueStatusHistory record.

Operator identity should be recorded for operator-initiated changes.

---

## 15. Photo / Media Handling

For local development:
- Laravel filesystem local disk is acceptable if needed

For production:
- prefer S3-compatible object storage

Do not store image binary data in PostgreSQL.

Use storage abstraction so deployment can switch disks.

Store metadata in IssuePhoto.

Resolution photos must be distinguishable from initial report photos.

---

## 16. Laravel Coding Standards

- Follow PSR-12.
- Use strict and clear type declarations where practical.
- Prefer constructor injection.
- Avoid static helper-heavy business logic.
- Keep controllers thin.
- Use Form Requests for non-trivial validation.
- Use Policies for authorization.
- Use API Resources for serialized output.
- Use Actions/Services for workflows involving multiple steps.
- Avoid fat controllers.
- Avoid putting complex workflow logic directly in Eloquent models.
- Avoid unnecessary traits.
- Avoid generic abstractions that hide simple Laravel behavior.
- Use transactions when a business operation changes multiple related records atomically.

---

## 17. Suggested Backend Flow

Example issue creation:

Controller
  -> StoreIssueRequest
  -> CreateIssueAction
  -> CivicIssue model
  -> photo handling if needed
  -> API Resource response

Example status change:

Controller
  -> UpdateIssueStatusRequest
  -> Policy authorization
  -> ChangeIssueStatusAction
  -> validate transition
  -> DB transaction
  -> update issue
  -> create status history
  -> return API Resource

---

## 18. Testing Expectations

Use Pest or PHPUnit consistently.

Prefer feature tests for HTTP/API behavior and unit tests for pure domain rules.

Critical workflows that must be tested:

- citizen registration/login
- create issue
- input validation
- authorization
- status transition rules
- invalid status transition rejection
- status history creation
- operator assignment
- issue confirmation
- duplicate confirmation prevention
- nearby geospatial query
- photo validation
- resolution workflow

Use a real PostgreSQL/PostGIS test environment for geospatial integration tests.

Do not rely on SQLite for tests that exercise PostgreSQL/PostGIS-specific behavior.

---

## 19. Frontend Expectations

Use React + TypeScript + Vite.

Suggested frontend areas:

src/
├── api/
├── components/
├── features/
│   ├── auth/
│   ├── issues/
│   ├── map/
│   └── operator/
├── pages/
├── routes/
├── types/
└── utils/

Do not build a large global state architecture unless genuinely needed.

Start simple.

Important screens:
- login/register
- public/live issue map
- create report
- issue detail
- citizen's reports
- operator dashboard
- operator issue detail
- analytics summary

---

## 20. Docker / Local Development

The local environment should ultimately support:

docker compose up -d

Expected services may include:
- backend
- frontend
- postgres/postgis
- optional object storage only when needed

Keep local setup understandable.

Do not introduce Kubernetes for MVP development.

---

## 21. Git / Change Discipline

When implementing a task:

1. inspect existing code first
2. read this CLAUDE.md
3. state understanding of the requirement
4. propose a short implementation plan
5. identify expected files to change
6. implement the smallest coherent change
7. run relevant tests
8. report what changed
9. report test results
10. mention tradeoffs or unresolved issues

Do not rewrite unrelated code.

Do not make large architectural changes without explaining them first.

Never delete working functionality merely to simplify implementation.

---

## 22. Claude Code Working Rules

Before implementing a substantial feature:

1. Read this CLAUDE.md.
2. Inspect the repository.
3. State your understanding.
4. Propose a short plan.
5. Call out assumptions.
6. Then implement.

For architecture-level requests:
- DO NOT immediately create production code.
- First present the proposed architecture, data model, API boundaries, Laravel structure, and implementation sequence.
- Wait for explicit approval before large structural changes.

If requirements conflict:
- explain the conflict
- prefer the simplest safe MVP-compatible solution

If unsure:
- avoid speculative infrastructure
- prefer Laravel conventions
- do not invent requirements

---

## 23. Initial Delivery Sequence

Build in this order unless there is a strong reason to change it:

1. repository structure
2. Laravel backend bootstrap
3. PostgreSQL/PostGIS local setup
4. database connectivity
5. migrations foundation
6. health endpoint
7. authentication with Sanctum
8. user roles/authorization foundation
9. CivicIssue domain model
10. create issue API
11. issue read/list APIs
12. photo upload
13. location storage
14. nearby issue query
15. duplicate warning
16. status workflow/history
17. operator dashboard APIs
18. departments and assignment
19. live map integration
20. community confirmation
21. resolution evidence
22. analytics
23. production/pilot hardening

---

## 24. First Milestone

The first technical milestone should be intentionally small.

Goal:
A clean Laravel API boots locally with PostgreSQL/PostGIS and exposes a health endpoint.

It should include:
- Laravel project
- PostgreSQL/PostGIS via docker compose
- environment configuration
- successful DB connection
- Flyway is NOT required because this is Laravel; use Laravel migrations
- one health endpoint
- automated test proving the health endpoint works
- README instructions for local startup

Do not implement CivicIssue or authentication in this first milestone unless explicitly requested.

---

## 25. Future Features

Possible later phases:

- AI image classification
- issue category suggestion
- image/text/location duplicate detection
- severity scoring
- rainfall/weather integration
- monsoon mode
- predictive waterlogging hotspots
- Redis caching
- queue workers
- SQS/Kafka where justified
- advanced notifications
- white-labeling
- full multi-tenancy
- SSO
- audit dashboards
- external municipal integrations
- public APIs
- native mobile applications

Do not implement these until explicitly requested.

---

## 26. Business Context

The citizen experience should remain free.

Potential paying customers:
- municipalities
- private townships
- housing communities
- university campuses
- industrial parks
- facility-management operators

The key paid value proposition is:
- issue intake
- GIS visibility
- prioritization
- assignment
- SLA management
- evidence
- transparency
- analytics
- operational accountability

The long-term vision is not merely "a complaint app".

CivicPulse should become an operating system for local civic issues:

Reporting -> GIS -> Workflow -> Field Operations -> Resolution -> Analytics -> Urban Intelligence
