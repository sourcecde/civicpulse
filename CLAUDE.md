# CLAUDE.md — CivicPulse

## 1. Project Overview

CivicPulse is a location-based civic incident and community-verification platform.

It is NOT merely a grievance/complaint management system. Its purpose is to make local civic conditions visible, easy to report, community-verifiable, actionable for authorities, and transparent to the public.

Core domain distinction:
- A **Report** is one citizen's observation/evidence about a problem at a place and time.
- An **Incident** is the underlying real-world civic problem.
- Multiple Reports and Confirmations may belong to one Incident.

Example: 50 citizens reporting the same pothole should normally result in one Incident with multiple reports/confirmations, not 50 independent Incidents.

Citizens should be able to report problems such as potholes, waterlogging, garbage, broken streetlights, drainage/sewer issues, water leakage, damaged roads/footpaths, open drains/manholes, and other civic infrastructure problems.

CivicPulse should support:
- photo/evidence
- GPS/location
- category
- short description
- nearby incident discovery
- community confirmation (e.g. "Still there")
- public incident visibility
- status/history
- authority updates when an authority participates
- citizen/community resolution verification
- recurring-hotspot detection later

The citizen should NOT need to know the ward, municipality, municipal corporation, Gram Panchayat, department, or responsible authority. CivicPulse should determine jurisdiction/routing from location wherever possible.

Kolkata can be the first pilot, but the domain must support all of West Bengal: cities, towns, municipalities, municipal corporations, and rural areas. Do NOT hard-code KMC or Kolkata into core models.

Long-term product direction:
CivicPulse should become a shared civic information layer between residents and local authorities:

Report -> Incident -> Community Verification -> Authority Action -> Resolution -> Public Verification -> Civic Intelligence

The public/citizen platform and core government dashboard are intended to remain free. Do not design core product decisions around charging citizens or government authorities.

---

## 2. Product Principles

1. CivicPulse is incident-centric, not complaint-centric.
2. Keep citizen reporting extremely simple; target roughly 10–20 seconds for the common flow.
3. A citizen Report and a civic Incident are separate concepts.
4. Prefer attaching a new observation to an existing nearby Incident when it is genuinely the same physical problem.
5. Citizens should not need administrative knowledge; location should drive jurisdiction and authority mapping.
6. "Around Me" / the live civic map is a core everyday-use feature, not an optional extra.
7. Community verification is core: users can confirm that an Incident still exists, add evidence, or verify resolution.
8. Public transparency is core: show factual lifecycle/progress without exposing private citizen data.
9. Do not falsely imply government participation. "Authority Acknowledged" is only valid after an authenticated authorized authority/operator actually acknowledges the Incident.
10. Government participation should complement existing grievance systems, not require replacing them.
11. Preserve audit/history. Do not silently erase inconvenient reports or status changes.
12. Build the MVP first and avoid premature infrastructure/AI complexity.
13. Prefer clear Laravel domain boundaries and maintainable code over clever abstractions.
14. Every important workflow should be testable.
15. Design for multiple jurisdictions/authorities, but do not build full multi-tenancy prematurely.
16. The core citizen and government experience should remain free; monetization is outside the core civic workflow and must not depend on selling personally identifiable citizen data.

---

## 3. MVP Scope

### Citizen Features

- Register / login
- Submit a Report about a civic problem
- Upload one or more photos/evidence
- Capture or select GPS location
- Select a category (manual first; automatic suggestion may come later)
- Add a short description
- Discover nearby active Incidents
- Attach/confirm an observation when the same Incident already exists
- View Incidents on an "Around Me" / live map
- View Incident detail and factual status/history
- Confirm "Still there"
- View own submitted Reports
- Follow resolution progress
- Verify whether a resolved Incident is actually fixed

### Public Transparency Features

- Public/live incident map
- Area-level factual civic progress
- Active / acknowledged / in-progress / resolved / reopened Incidents
- Confirmation counts and recent public evidence where safe
- Status timeline
- Resolution evidence where available
- Citizen/community verification of resolution

Do not expose reporter email, phone, exact private address, or other private account data publicly.

### Authority / Operator Features

- Authenticated operator login
- Incident dashboard/map
- Filter by status, category, priority, administrative area, date, authority, and department
- View consolidated Incident with underlying Reports/confirmations/evidence
- Acknowledge Incident
- Set operational priority
- Assign authority/department/team where applicable
- Update status
- Add internal/operator notes
- Add resolution note/evidence
- View recurring/problem hotspot information when implemented
- View simple operational analytics

### Initial Incident Status Workflow

Use a controlled state model. Initial candidates:

- REPORTED
- COMMUNITY_VERIFIED
- ROUTED
- AUTHORITY_ACKNOWLEDGED
- ASSIGNED
- WORK_IN_PROGRESS
- RESOLVED
- RESOLUTION_VERIFIED
- REOPENED

Transitions must be validated in backend logic. Do not allow arbitrary status changes.

Keep authority status and community verification conceptually distinct. An authority marking an Incident RESOLVED does not automatically mean citizens have verified the physical problem is gone. See §14 — this is not a single linear status chain.

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

Start as a modular monolith and use Laravel conventions first.

Suggested application structure:

app/
├── Actions/
│   ├── Reports/
│   └── Incidents/
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

Core domain areas:
- identity
- reports
- incidents
- evidence/media
- locations/geospatial
- confirmations
- administrative areas
- authorities
- departments
- assignments
- workflow/status history
- public transparency
- analytics

Do not split these into deployable microservices in the MVP.
Do not create generic BaseService/BaseRepository layers without a concrete need.
Eloquent is the default persistence abstraction.
Controllers should remain thin; use Actions/Services for meaningful workflows.

### Authority-agnostic rule

Never make KMC-specific fields fundamental to the domain. Avoid fields such as `kmc_ward_id` in core entities.

Prefer generic models such as:
- AdministrativeArea
- Authority
- Department
- Location / geometry

These must be capable of representing, over time:
- state
- district
- municipal corporation
- municipality
- ward
- block
- Gram Panchayat
- other relevant administrative/service boundaries

Do not implement the entire West Bengal hierarchy in the MVP. Preserve the abstraction so it can be added incrementally.

---

## 7. Core Domain Model

### User
Fields:
- id
- name
- email
- password
- role
- created_at
- updated_at

Roles:
- CITIZEN
- OPERATOR
- ADMIN

### Report
Represents one citizen observation/evidence submission.

Likely fields:
- id
- public_reference
- reporter_id
- incident_id nullable during creation/matching if needed
- category
- description nullable
- latitude
- longitude
- geography/location field where appropriate
- address_text nullable
- observed_at / created_at
- updated_at

A Report must not be treated as the long-lived physical problem itself.

### Incident
Represents the underlying civic problem that may aggregate multiple Reports and Confirmations.

Likely fields:
- id
- public_reference
- short_summary
- category
- status
- priority nullable
- canonical latitude/longitude or geography
- address_text nullable
- administrative_area_id nullable
- authority_id nullable
- department_id nullable
- assigned_to_id nullable
- first_reported_at
- last_confirmed_at nullable
- resolved_at nullable
- created_at
- updated_at

### Evidence / ReportPhoto
Evidence should be attributable to the Report or Incident/action that produced it.

Fields may include:
- id
- report_id nullable
- incident_id nullable where appropriate
- uploaded_by
- storage_disk
- storage_path
- type
- created_at

Types may include:
- REPORT
- FOLLOW_UP
- RESOLUTION

### Confirmation / observation history (design pending)
Represents citizens confirming that an Incident still exists ("Still there"), and later confirming or disputing that it has been fixed.

The database design is intentionally NOT decided yet and must be designed separately before implementation. Repeated confirmations by the same user over time (e.g. the same waterlogged street confirmed on several days) may be valuable civic data, so do NOT assume a simple one-row-per-user-per-Incident unique constraint.

Whatever design is chosen must still:
- attribute each confirmation to an authenticated user and a time
- prevent meaningless spam/abuse (e.g. rapid repeat taps) through explicit, tested business rules
- keep "still present" signals distinct from resolution-verification signals
- never expose the confirming user's private details publicly

### IncidentStatusHistory
Fields:
- id
- incident_id
- previous_status nullable
- new_status
- changed_by nullable/system where appropriate
- comment nullable
- created_at

Status history is append-only audit history for normal workflows.

### AdministrativeArea
Generic jurisdiction/geographic hierarchy. Keep flexible enough for urban and rural West Bengal without implementing every level immediately.

Possible fields:
- id
- parent_id nullable
- type
- name
- geometry nullable
- active

### Authority
Represents the organization responsible for a class/location of civic work.

Possible fields:
- id
- name
- authority_type
- administrative_area_id nullable
- active

### Department
Possible fields:
- id
- authority_id nullable
- name
- active

### Recurring hotspot (later)
Do not create a complex hotspot subsystem in the first implementation. Preserve enough incident/location/history data so recurring physical problems can later be detected from repeated Incidents near the same location/category over time.

---

## 8. Enums

Prefer PHP backed enums for stable domain values.

Examples:
- IncidentStatus
- IncidentPriority
- CivicCategory
- UserRole
- EvidenceType

Do not scatter magic status/category strings throughout the codebase.

---

## 9. Geospatial Requirements

Use PostgreSQL + PostGIS.

Primary MVP geospatial use cases:
- display nearby Incidents
- find possible matching Incidents within a configurable radius before creating a new Incident
- filter Incidents by map viewport
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

Example endpoints (exact naming may evolve after repository inspection):

### Authentication
- POST /api/register
- POST /api/login
- POST /api/logout
- GET /api/me

### Reports
- POST /api/reports
- GET /api/reports/mine
- GET /api/reports/{report}

### Incidents
- GET /api/incidents
- GET /api/incidents/{incident}
- GET /api/incidents/nearby
- GET /api/incidents/{incident}/history

### Confirmations / verification
- POST /api/incidents/{incident}/confirmations
- POST /api/incidents/{incident}/resolution-verifications

### Authority/operator actions
- PATCH /api/incidents/{incident}/status
- PATCH /api/incidents/{incident}/priority
- PATCH /api/incidents/{incident}/assignment

### Media
Prefer evidence upload as part of or clearly linked to Report/Incident workflows; exact endpoints should follow the implemented storage flow.

Guidelines:
- use Form Request validation
- use API Resources for responses
- never return Eloquent models directly as public API contracts
- use route model binding where appropriate
- provide consistent API error responses
- use correct HTTP status codes
- paginate collection endpoints
- avoid leaking internal/private citizen fields
- explicitly authorize every operator-only action
- keep Report and Incident API semantics distinct

---

## 11. Authentication and Authorization

Use Laravel Sanctum for first-party API authentication unless a stronger requirement appears.

Authorization rules:
- citizens can submit Reports and view allowed/public Incidents
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
- Do not expose private citizen details in public Report or Incident responses.
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

Public Report and Incident references should be separate from internal numeric/UUID IDs when useful.

Do not encode Kolkata/KMC as a permanent assumption in public references. Prefer a neutral or configurable public-reference strategy.

---

## 14. Status Transition Rules

Status changes are domain behavior, not generic CRUD.

Create one clear place that decides whether a transition is allowed.

Canonical Incident statuses:

- REPORTED
- COMMUNITY_VERIFIED
- ROUTED
- AUTHORITY_ACKNOWLEDGED
- ASSIGNED
- WORK_IN_PROGRESS
- RESOLVED
- RESOLUTION_VERIFIED
- REOPENED

ROUTED is the canonical term for "sent to / mapped to the responsible authority". Do not use FORWARDED.

### Two separate concerns

The lifecycle is NOT one simplistic linear chain. Two different concerns act on an Incident and must be modeled and reasoned about separately:

1. **Authority workflow** — what the responsible authority/operator says it is doing: ROUTED, AUTHORITY_ACKNOWLEDGED, ASSIGNED, WORK_IN_PROGRESS, RESOLVED. These are set only by authenticated, authorized operators (or by explicit system routing for ROUTED).
2. **Citizen/community verification** — what residents observe on the ground: confirmations that the problem exists (COMMUNITY_VERIFIED) and verification that a claimed resolution actually fixed it (RESOLUTION_VERIFIED) or did not (REOPENED). These are driven by community evidence under explicit, tested rules, never by an operator simply choosing the status.

Consequences:
- An operator marking RESOLVED is a claim, not proof. It must not automatically become RESOLUTION_VERIFIED.
- Operators must not be able to set RESOLUTION_VERIFIED themselves.
- Community evidence that a RESOLVED Incident is still present can lead to REOPENED; a REOPENED Incident re-enters the authority workflow.
- Steps may be skipped (e.g. an Incident may never be COMMUNITY_VERIFIED before being ROUTED, or no authority may participate at all). Not every deployment or jurisdiction uses every status.
- Allowed transitions are therefore a graph that depends on both the current status and who/what is acting (operator, community rule, system), not a fixed sequence.

Whether community verification is stored in the single Incident status field, in a separate verification state, or both, is to be decided in the Incident design. Whatever is chosen, the two concerns must stay distinguishable in data, API responses and the public timeline.

Invalid transitions should return a domain/validation error.

Every successful Incident status transition must create an IncidentStatusHistory record.

Operator identity should be recorded for operator-initiated changes. Community- or system-driven changes must record that they were community/system driven.

---

## 15. Photo / Media Handling

For local development:
- Laravel filesystem local disk is acceptable if needed

For production:
- prefer S3-compatible object storage

Do not store image binary data in PostgreSQL.

Use storage abstraction so deployment can switch disks.

Store metadata in the appropriate evidence/media model.

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

Example Report creation:

Controller
  -> StoreReportRequest
  -> CreateReportAction
  -> validate location/category/evidence
  -> search for nearby candidate Incidents using PostGIS
  -> attach to an existing Incident when explicitly/validly matched OR create a new Incident
  -> persist Report/evidence
  -> return API Resource response

Do not begin with AI duplicate detection. Initial matching should be explainable and simple, based primarily on category + distance + relevant time/status rules, with user confirmation where ambiguity exists.

Example Incident status change:

Controller
  -> UpdateIncidentStatusRequest
  -> Policy authorization
  -> ChangeIncidentStatusAction
  -> validate transition
  -> DB transaction
  -> update Incident
  -> create IncidentStatusHistory
  -> return API Resource

---

## 18. Testing Expectations

Use Pest or PHPUnit consistently.

Prefer feature tests for HTTP/API behavior and unit tests for pure domain rules.

Critical workflows that must be tested:

- citizen registration/login
- submit Report
- create/attach Incident from Report
- input validation
- authorization
- status transition rules
- invalid status transition rejection
- status history creation
- Incident/authority assignment
- Incident confirmation
- confirmation abuse/spam prevention rules (once the confirmation design is agreed)
- community resolution verification and reopening
- operators cannot self-verify resolution
- nearby Incident geospatial query
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
│   ├── reports/
│   ├── incidents/
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
- Around Me / public live incident map
- create Report
- Incident detail
- citizen's Reports
- civic progress/public transparency
- operator dashboard
- operator Incident detail
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

Build in this order unless existing repository progress means a step is already complete:

1. repository structure
2. Laravel backend bootstrap
3. PostgreSQL/PostGIS local setup
4. database connectivity
5. migrations foundation
6. health endpoint
7. authentication with Sanctum
8. user roles/authorization foundation
9. Report domain model and create Report API
10. Incident domain model
11. Report -> Incident creation/attachment workflow
12. location/geography storage
13. nearby Incident query (Around Me backend)
14. community confirmation / "Still there"
15. simple duplicate/matching warning using category + distance + time/status rules
16. Incident status workflow/history
17. evidence/photo upload
18. generic AdministrativeArea / Authority / Department foundation
19. public live map / Civic Progress APIs
20. authority/operator dashboard APIs
21. assignment/routing foundation
22. resolution evidence
23. community resolution verification / reopen review flow
24. recurring-hotspot analytics
25. production/pilot hardening

The core product loop to prove first is:

REPORT -> INCIDENT -> NEARBY -> CONFIRM -> STATUS -> VERIFY

Do not let later analytics, AI, government integration, or native apps delay proving this loop.

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

This historical first milestone may already be complete. Do not redo completed foundation work merely because it appears in this document.

---

## 25. Future Features

Possible later phases:

- AI image classification
- Report category suggestion
- advanced image/text-assisted duplicate detection
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

## 26. Public-Service and Sustainability Context

Founding product principle:
- citizens should be able to use CivicPulse core services for free
- participating government/local authorities should be able to use the core CivicPulse dashboard for free
- do not make the core civic workflow depend on per-report fees, government licence fees, or selling personally identifiable citizen data

CivicPulse may later be sustained through mechanisms outside the core citizen/government workflow, such as grants, CSR/sponsorship with strict independence, or separate commercial technology/products. Those business models must not control incident visibility, suppress reports, or compromise citizen privacy.

The product should make participation valuable to both sides:

Citizen value:
- report quickly
- know what is happening nearby
- avoid duplicate complaint effort
- follow progress
- verify outcomes

Authority value:
- receive consolidated incidents instead of complaint noise
- see geographic hotspots
- understand scale through confirmations/evidence
- route/assign work
- identify recurring failures
- show completed work transparently

The long-term vision is not "a better complaint form".

CivicPulse should become a civic incident and intelligence layer:

Citizen Observations -> Verified Incidents -> Geospatial Awareness -> Authority Action -> Resolution Verification -> Civic Intelligence

