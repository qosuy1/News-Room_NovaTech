# NewsRoom API

NewsRoom is a Laravel API for a newsroom workflow. It helps writers publish articles, lets admins review and archive content, and supports readers with comments, tags, and attachments.

## What it does

- Manages articles through `draft`, `published`, and `archived` states.
- Supports role-based access for `admin`, `writer`, and `reader` users.
- Sends notifications and runs background jobs.
- Includes scheduled archive and report commands.

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

7. Install frontend dependencies and build if needed:

```bash
npm install
npm run build
```

8. Start the API:

```bash
php artisan serve
```

9. Start the queue worker:

```bash
php artisan queue:work
```

10. Run scheduled commands manually:

```bash
php artisan schedule:run
```

11. Run tests:

```bash
composer test
```

## Artisan commands

Archive draft articles older than 30 days:

```bash
php artisan articles:archive
```

Override the default days:

```bash
php artisan articles:archive 45
```

Generate the published articles report:

```bash
php artisan articles:report --days=7
```

## Scheduler

Defined in `routes/console.php`:

```php
Schedule::command('articles:archive 30')->monthlyOn(1, '00:00');
Schedule::command('articles:report')->weeklyOn(5, '08:00');
```

## Core concepts

### Roles

- `writer`: creates and manages articles.
- `admin`: reviews, archives, and monitors content.
- `reader`: views published articles and adds comments.

### Articles

- Main content entity with `draft`, `published`, and `archived` statuses.
- Supports tags, attachments, and comments.
- Uses queued notifications for side effects.

### Polymorphic relations

Used for:

- profiles,
- comments,
- attachments,
- tags.

This keeps the schema flexible across different models.

## Architecture decisions

### 1. Versioned API routes

**Problem:** API responses and behavior can change over time. If all routes live in one file with one controller version, adding a new response format later can break existing clients.

**What I decided:** I separated the API into versioned route files:

- `routes/Versioning/v1_api.php`
- `routes/Versioning/v2_api.php`

**How it works:** The main `routes/api.php` file only registers high-level prefixes:

```php
Route::prefix('v1')->group(function () {
    require __DIR__.'/Versioning/v1_api.php';
});

Route::prefix('v2')->group(function () {
    require __DIR__.'/Versioning/v2_api.php';
});
```

Each version can point to its own controllers and resources, for example V1 and V2 article resources.

**Why:** This makes the API safer to evolve. I can improve V2 without changing the response shape expected by V1 clients.

### 2. Thin controllers

**Problem:** If controllers contain validation, authorization, database queries, file uploads, notifications, and response formatting, they become hard to read and hard to test.

**What I decided:** Controllers should only handle HTTP flow:

- receive the request,
- call a service or repository,
- return an API response/resource.

**How it works:** Controllers such as `ArticleController` call `ArticleService` for business operations and return `ArticleResource` or `ApiResponse`.

**Why:** This keeps controllers focused on the API layer. Business logic can be reused from commands, jobs, or future controllers without copying code.

### 3. Service layer for business logic

**Problem:** Article creation, publishing, attachment upload, tag syncing, dashboard statistics, and notifications are workflows, not simple database calls.

**What I decided:** I created services such as:

- `ArticleService`
- `AttachmentService`
- `DashboardService`

**How it works:** The controller sends validated data to the service. The service handles transactions, relations, uploads, cache invalidation, events, and jobs.

**Why:** Services make complex workflows easier to maintain. They also reduce duplication because the same service method can be called from different parts of the app.

### 4. Repository pattern for article queries

**Problem:** Article queries can become complicated because articles need filters, relations, ordering, pagination, publishing status, and optimized loading.

**What I decided:** I added an article repository contract and implementation:

- `ArticleRepositoryInterface`
- `EloquentArticleRepository`

**How it works:** `ArticleService` depends on the interface, not directly on the Eloquent implementation. The binding is registered in `AppServiceProvider`.

**Why:** This separates business logic from query details. If article storage or query optimization changes later, the service layer does not need to change much.

### 5. Dependency injection and contracts

**Problem:** If classes manually create their dependencies, the code becomes tightly coupled and harder to test.

**What I decided:** I used Laravel dependency injection with interfaces.

**How it works:** `AppServiceProvider` binds contracts to implementations:

```php
$this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
$this->app->bind(AttachmentServiceInterface::class, AttachmentService::class);
$this->app->bind(NotificationDispatcherInterface::class, RoleBasedNotificationDispatcher::class);
```

**Why:** Controllers and services can request what they need through constructors. Laravel resolves the correct implementation automatically.

### 6. Form requests for validation and authorization

**Problem:** Validation rules and authorization checks can clutter controllers, especially when every resource has different rules.

**What I decided:** I moved request validation into form request classes under `app/Http/Requests/V1`.

**How it works:** Examples include:

- `StoreArticleRequest`
- `UpdateArticleRequest`
- `RegisterRequest`
- `LoginRequest`
- tag, comment, profile, and attachment requests

These classes handle authorization, validation rules, custom messages, and input preparation.

**Why:** This keeps controller methods clean and makes validation easier to find, test, and update per API version.

### 7. API resources and response helper

**Problem:** Returning raw Eloquent models can expose unwanted fields and create inconsistent API responses.

**What I decided:** I used API resources and a shared response helper.

**How it works:**

- `ArticleResource` controls the article response structure.
- `DashboardResource` controls dashboard output.
- `ApiResponse` standardizes success, created, error, and paginated responses.

**Why:** The API response becomes predictable for frontend/mobile clients, and internal database structure stays hidden.

### 8. Role-based authorization middleware

**Problem:** Admins, writers, and readers do not have the same permissions. Checking roles manually in every controller action would be repetitive and error-prone.

**What I decided:** I added role and article action middleware.

**How it works:**

- `AuthorizeUser` checks simple role-based access.
- `AuthorizeArticleAction` checks actions such as view, update, delete, and publish.
- Routes attach middleware where the rule is needed.

**Why:** Authorization stays close to the route being protected, and the same rule can be reused across endpoints.

### 9. Polymorphic relations

**Problem:** Comments, attachments, and profiles may belong to different models in the future. Creating separate tables for every model would make the schema harder to extend.

**What I decided:** I used polymorphic relationships for reusable features.

**How it works:**

- `Article` has morph many `comments`.
- `Article` has morph many `attachments`.
- `User` has morph one `profile`.
- `Tag` uses a polymorphic many-to-many relation through `taggables`.

**Why:** The schema is flexible. For example, attachments can later be added to another model without creating a new attachment table.

### 10. Events, jobs, observers, and notifications

**Problem:** Publishing an article can trigger many side effects: notify readers, notify writers/admins, update dashboard cache, and send reports. Doing all of this directly in the request would slow the API down.

**What I decided:** I separated side effects using Laravel events, jobs, observers, listeners, and notifications.

**How it works:**

- `ArticlePublished` represents the publish event.
- queued jobs handle slower work.
- observers react to model lifecycle changes.
- listeners invalidate dashboard cache.
- notification dispatchers choose how notifications are sent.

**Why:** Article actions stay fast and focused. Side effects become isolated, easier to retry, and easier to change.

### 11. Rate limiting

**Problem:** Some routes are more sensitive than others. Login, register, create, update, and delete actions should be protected more strongly than read-only endpoints.

**What I decided:** I added two rate limiters:

- `api`: general API traffic.
- `strict`: write-heavy or sensitive actions.

**How it works:** `AppServiceProvider` defines the limits, and routes apply `throttle:strict` where needed.

**Why:** This protects the app from spam and abuse while still allowing normal API usage.

### 12. Scheduled commands

**Problem:** Archiving old articles and generating reports should not require an admin to manually trigger them every time.

**What I decided:** I used Laravel scheduled commands.

**How it works:** `routes/console.php` schedules:

- `articles:archive`
- `articles:report`

The archive command can receive a custom days value, and the report command can generate logs for a selected period.

**Why:** Recurring maintenance becomes automatic and predictable.

### 13. Separate logging channels

**Problem:** Mixing API logs, report logs, and Laravel system logs in one file makes debugging harder.

**What I decided:** I added dedicated logging channels.

**How it works:**

- `api` writes API activity logs.
- `reports` writes generated article report logs.
- default Laravel logs remain separate.

**Why:** Logs are easier to read, search, and audit.

### 14. Queues and Redis

**Problem:** Notifications and report emails can take time. Running them during the HTTP request would make users wait.

**What I decided:** I configured queue support and Redis/Predis.

**How it works:** Jobs are dispatched to the queue, and a worker processes them in the background:

```bash
php artisan queue:work
```

**Why:** The API stays responsive, and background work can be retried if something fails.
