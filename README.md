# NewsRoom API

NewsRoom is a Laravel API for a newsroom workflow. It helps writers publish articles, lets admins monitor and archive content, and supports readers with published article feeds, comments, tags, attachments, notifications, reports, and scheduled maintenance.

## Setup

1. Clone the repository:

```bash
git clone https://github.com/qosuy1/News-Room_NovaTech.git
cd NewsRoom_Task5
```

2. Install PHP dependencies:

```bash
composer install
```

3. Create the environment file:

```bash
cp .env.example .env
```

On PowerShell:

```powershell
Copy-Item .env.example .env
```

4. Generate the application key:

```bash
php artisan key:generate
```

5. Configure the database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_room_db
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations:

```bash
php artisan migrate
```

7. Install frontend dependencies and build assets if needed:

```bash
npm install
npm run build
```

8. Start the API:

```bash
php artisan serve
```

9. Start the queue worker for queued notifications and report jobs:

```bash
php artisan queue:work
```

10. Run scheduled commands manually while developing:

```bash
php artisan schedule:run
```

11. Run tests:

```bash
composer test
```

## Commands

Archive non-published articles older than the default 30 days:

```bash
php artisan articles:archive
```

Archive non-published articles with a custom age:

```bash
php artisan articles:archive --days=45
```

Generate a published articles report for the last 7 days:

```bash
php artisan articles:report --days=7
```

Run all due scheduled tasks:

```bash
php artisan schedule:run
```

The scheduler is defined in `routes/console.php`:

```php
Schedule::command('articles:archive --days=30')->monthlyOn(1, '00:00');
Schedule::command('articles:report')->weeklyOn(5, '08:00');
```

## Entities And Relations

### User

Users represent `admin`, `writer`, and `reader` accounts.

- A user has one profile through a polymorphic `profileable` relation.
- A user has many articles.
- A user has many comments.
- A user can receive notifications.
- Role helper methods such as `isAdmin()`, `isWriter()`, and `isReader()` are used by authorization logic.

### Profile

Profiles store extra account information.

- A profile belongs to a `profileable` model.
- In this project, the main profile owner is `User`.

### Article

Articles are the main newsroom content entity.

- An article belongs to one writer through `user_id`.
- An article has many comments through a polymorphic `commentable` relation.
- An article has many attachments through a polymorphic `attachable` relation.
- An article belongs to many tags through the polymorphic `taggables` pivot table.
- An article has a `status`: `draft`, `published`, or `archived`.
- A published article can store `published_at`.

### Comment

Comments belong to users and can be attached to commentable models.

- A comment belongs to one user.
- A comment belongs to a `commentable` model.
- Articles can have many comments.

### Attachment

Attachments store uploaded files.

- An attachment belongs to an `attachable` model.
- Articles can have many attachments.
- Attachment data includes path/disk/original name/file type depending on the upload flow.

### Tag

Tags organize article topics.

- A tag belongs to many articles through `taggables`.
- Tags use a slug mutator to keep URL-friendly names.

### Relation Summary

```text
User 1 -------- * Article
User 1 -------- 1 Profile, through profileable polymorphic relation
User 1 -------- * Comment

Article 1 ----- * Comment, through commentable polymorphic relation
Article 1 ----- * Attachment, through attachable polymorphic relation
Article * ----- * Tag, through taggables polymorphic pivot
```

## Core Concepts

### Roles

- `writer`: creates, updates, deletes, and publishes allowed articles.
- `admin`: manages protected actions such as tags and can monitor system behavior.
- `reader`: views published content and receives reader-focused notifications.

### Article lifecycle

Articles move through `draft`, `published`, and `archived` states. Publishing can trigger events, queued notifications, and cache invalidation. Archiving can run automatically through the scheduler.

### Polymorphic relations

Profiles, comments, attachments, and tags use polymorphic-style modeling where it gives the project flexibility. The main benefit is that reusable features can later attach to other models without redesigning the whole database.

### Versioned public article feed

The home feed is versioned clearly:

- `GET /api/v1/` uses `App\Http\Controllers\Api\V1\HomeController`.
- `GET /api/v2/` uses `App\Http\Controllers\Api\V2\HomeController`.

V1 loads published articles with their writer:

```php
$articles = $this->articleRepository->getAllPublishedWithRelations(15, ['user']);
```

V1 returns a simple article feed through `App\Http\Resources\V1\HomeResource`:

```json
{
  "id": 15,
  "title": "Local Newsroom Publishes New Report",
  "content": "Article body...",
  "writer_name": "Sara Writer",
  "published_at": "2026-05-26 10:15:00"
}
```

V2 loads published articles with writer and tags:

```php
$articles = $this->articleRepository->getAllPublishedWithRelations(15, ['user', 'tags']);
```

V2 uses `App\Http\Resources\V2\HomeResource` to extend the home response with richer metadata such as tags, reading time, and comment count. This is the practical reason for versioning: V2 can improve the public article feed without breaking clients that still depend on V1.

## Architecture Decisions

### 1. Versioned API routes

**Problem:** API responses and behavior can change over time. If all routes live in one version, changing a response can break existing clients.

**What:** The API is split into versioned route files:

- `routes/Versioning/v1_api.php`
- `routes/Versioning/v2_api.php`

**How:** `routes/api.php` loads each file under a version prefix:

```php
Route::prefix('v1')->group(function () {
    require __DIR__.'/Versioning/v1_api.php';
});

Route::prefix('v2')->group(function () {
    require __DIR__.'/Versioning/v2_api.php';
});
```

**Why:** V1 can stay stable while V2 introduces richer responses, like the V2 home article feed.

### 2. Thin controllers

**Problem:** Controllers become hard to read when they contain validation, authorization, queries, uploads, notifications, and formatting.

**What:** Controllers only handle HTTP flow: receive request, call service/repository, return response.

**How:** `ArticleController` delegates article workflows to `ArticleService`, then returns `ArticleResource` or `ApiResponse`.

**Why:** Business logic stays reusable and controllers stay focused on API behavior.

### 3. Service layer

**Problem:** Creating, updating, publishing, uploading attachments, syncing tags, and dispatching notifications are workflows, not simple controller actions.

**What:** Business logic lives in services:

- `ArticleService`
- `AttachmentService`
- `DashboardService`

**How:** Services coordinate transactions, repositories, file uploads, relations, events, jobs, and cache updates.

**Why:** Complex workflows become easier to maintain and reuse.

### 4. Repository pattern

**Problem:** Article queries need relations, pagination, status filters, ordering, and optimized loading.

**What:** Article query logic is placed behind:

- `ArticleRepositoryInterface`
- `EloquentArticleRepository`

**How:** Services depend on the interface, and Laravel resolves the implementation through `AppServiceProvider`.

**Why:** Query details are separated from business logic.

### 5. Dependency injection and contracts

**Problem:** Manually creating dependencies tightly couples classes together.

**What:** Interfaces are bound to implementations.

**How:** `AppServiceProvider` registers bindings:

```php
$this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
$this->app->bind(AttachmentServiceInterface::class, AttachmentService::class);
$this->app->bind(NotificationDispatcherInterface::class, RoleBasedNotificationDispatcher::class);
```

**Why:** Laravel can inject the correct dependency automatically, making the code cleaner and easier to change.

### 6. Form requests

**Problem:** Validation and authorization rules can clutter controllers.

**What:** Request rules live in `app/Http/Requests/V1`.

**How:** Classes such as `StoreArticleRequest`, `UpdateArticleRequest`, `RegisterRequest`, and `LoginRequest` handle validation, authorization, messages, and input preparation.

**Why:** Validation is easier to find, test, and change per API version.

### 7. Resources and response helper

**Problem:** Returning raw Eloquent models can expose unwanted fields and create inconsistent API shapes.

**What:** API output is shaped with resources and `ApiResponse`.

**How:** `ArticleResource`, `DashboardResource`, `HomeResource`, and `ApiResponse` define consistent response structures.

**Why:** Clients get predictable JSON, and internal model details stay controlled.

### 8. Role-based middleware

**Problem:** Admins, writers, and readers need different permissions.

**What:** Authorization is handled through middleware and user role helpers.

**How:** `AuthorizeUser` handles role checks, and `AuthorizeArticleAction` handles article-specific actions like view, update, delete, and publish.

**Why:** Route protection stays consistent and reusable.

### 9. Events, jobs, notifications, and observers

**Problem:** Publishing an article can trigger slow side effects, such as notifications and cache invalidation.

**What:** Side effects are separated into events, jobs, listeners, observers, and notifications.

**How:** `ArticlePublished`, queued jobs, notification dispatchers, and observers react to article/user changes.

**Why:** Article requests stay fast, and background work becomes easier to retry and maintain.

### 10. Rate limiting

**Problem:** Login, register, create, update, and delete actions are more sensitive than public reads.

**What:** The app uses `api` and `strict` rate limiters.

**How:** `AppServiceProvider` defines the limits, and routes apply `throttle:strict` for write-heavy actions.

**Why:** The app is better protected from spam and abuse.

### 11. Scheduled commands

**Problem:** Reports and archive maintenance should not require manual work every time.

**What:** The app uses Laravel scheduled commands.

**How:** `routes/console.php` schedules `articles:archive`, `articles:report`, and weekly report jobs.

**Why:** Recurring maintenance becomes automatic and predictable.

### 12. Separate logging channels

**Problem:** API logs and report logs are hard to inspect if everything goes to one file.

**What:** Dedicated `api` and `reports` logging channels are configured.

**How:** `config/logging.php` defines separate daily log files.

**Why:** Debugging and auditing become easier.

### 13. Queues and Redis

**Problem:** Notifications and report emails can make requests slow.

**What:** Background jobs are processed through queues, with Redis/Predis support configured.

**How:** Run a worker with:

```bash
php artisan queue:work
```

**Why:** Slow work moves out of the request lifecycle.

## Project Structure

### `app/`

```text
app/
|-- Console/Commands       Scheduled and manually executed Artisan commands.
|-- Enums                  Shared enum-like values such as cache keys.
|-- Events                 Domain events such as article publication.
|-- Helper/V1              Shared API response helper for V1.
|-- Http/Controllers/Api   Versioned API controllers.
|-- Http/Middleware        Authorization, article permissions, and request logging.
|-- Http/Requests/V1       Versioned form request validation.
|-- Http/Resources         API response transformers.
|-- Interfaces             Contracts for repositories and services.
|-- Jobs                   Queue jobs for reports and notifications.
|-- Listeners              Event listeners such as cache invalidation.
|-- Mail                   Mailable classes for reports.
|-- Models                 Eloquent entities and relationships.
|-- Notifications          Laravel notification classes.
|-- Observers              Model lifecycle observers.
|-- Repository             Eloquent repository implementations.
|-- Rules                  Custom validation rules.
`-- Services               Business workflows and notification dispatching.
```

## API Response Examples

The API uses this shared response style from `App\Helper\V1\ApiResponse`:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

### Register

Request:

```http
POST /api/auth/register
Content-Type: application/json
Accept: application/json
```

```json
{
  "name": "Sara Writer",
  "email": "sara@example.com",
  "password": "password",
  "password_confirmation": "password",
  "role": "writer"
}
```

Response:

```json
{
  "success": true,
  "message": "account created successfully!",
  "data": {
    "user": {
      "id": 1,
      "name": "Sara Writer",
      "email": "sara@example.com",
      "role": "writer"
    },
    "access_token": "1|plain-text-token",
    "token_type": "Bearer"
  }
}
```

### Login

```http
POST /api/auth/login
Content-Type: application/json
Accept: application/json
```

```json
{
  "email": "sara@example.com",
  "password": "password"
}
```

```json
{
  "success": true,
  "message": "login successful and token generated.",
  "data": {
    "user": {
      "id": 1,
      "name": "Sara Writer",
      "email": "sara@example.com",
      "role": "writer"
    },
    "access_token": "2|plain-text-token",
    "token_type": "Bearer"
  }
}
```

### V1 home article feed

```http
GET /api/v1/
Accept: application/json
```

```json
{
  "success": true,
  "message": "Articles retrieved successfully.",
  "data": [
    {
      "id": 15,
      "title": "Local Newsroom Publishes New Report",
      "content": "Article body...",
      "writer_name": "Sara Writer",
      "published_at": "2026-05-26 10:15:00"
    }
  ]
}
```

### V2 home article feed

```http
GET /api/v2/
Accept: application/json
```

V2 is intended to enrich the home article feed with tags and metadata while keeping V1 stable:

```json
{
  "success": true,
  "message": "Articles retrieved successfully.",
  "data": [
    {
      "id": 15,
      "title": "Local Newsroom Publishes New Report",
      "content": "Article body...",
      "writer_name": "Sara Writer",
      "published_at": "2026-05-26 10:15:00",
      "tags": [
        {
          "id": 2,
          "name": "Politics"
        }
      ],
      "meta": {
        "reading_time": 3,
        "comment_count": 5
      }
    }
  ]
}
```

### Article list

```http
GET /api/v1/articles?per_page=10
Accept: application/json
```

```json
{
  "success": true,
  "message": "Articles retrieved successfully.",
  "data": [
    {
      "articles": {
        "id": 15,
        "title": "Local Newsroom Publishes New Report",
        "content": "Article body...",
        "status": "published",
        "published_at": "2026-05-26 10:15:00",
        "comments_count": 3
      },
      "writer": {
        "id": 1,
        "name": "Sara Writer",
        "email": "sara@example.com"
      },
      "tags": [
        {
          "id": 2,
          "name": "Politics",
          "slug": "politics"
        }
      ],
      "attachments": []
    }
  ],
  "links": {},
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 25
  }
}
```

### Create article

```http
POST /api/v1/articles
Accept: application/json
Authorization: Bearer 2|plain-text-token
```

```json
{
  "title": "How Newsrooms Handle Breaking Stories",
  "content": "Long article content with at least one hundred characters...",
  "status": "draft",
  "tags": [1, 2]
}
```

```json
{
  "success": true,
  "message": "Article created successfully.",
  "data": {
    "articles": {
      "id": 16,
      "title": "How Newsrooms Handle Breaking Stories",
      "content": "Long article content with at least one hundred characters...",
      "status": "draft",
      "published_at": null,
      "comments_count": null
    },
    "writer": {
      "id": 1,
      "name": "Sara Writer",
      "email": "sara@example.com"
    },
    "comments": [],
    "tags": [],
    "attachments": []
  }
}
```

### Publish article

```http
PATCH /api/v1/articles/16/publish
Accept: application/json
Authorization: Bearer 2|plain-text-token
```

```json
{
  "success": true,
  "message": "Article published successfully.",
  "data": {
    "articles": {
      "id": 16,
      "title": "How Newsrooms Handle Breaking Stories",
      "status": "published",
      "published_at": "2026-05-26 10:30:00"
    }
  }
}
```

### Validation error

Laravel form requests may return the default validation shape before the controller runs:

```json
{
  "message": "The title field is required. (and 1 more error)",
  "errors": {
    "title": [
      "Article title is required."
    ],
    "content": [
      "Article content must be at least 100 characters."
    ]
  }
}
```
