You are building a professional Laravel development package named **laravel-model-graph** and vendor name "Matakltm".

This package analyzes a Laravel project and generates a JSON representation of all Eloquent models and their relationships. The JSON is consumed by a prebuilt React (React Flow) SPA to visualize the full database model graph.

The React frontend is already built separately and compiled assets will exist inside:

```
resources/dist/
```

Your task is to generate the complete Laravel package backend with production-grade architecture.

---

# 🎯 PACKAGE OBJECTIVE

Build a Laravel package that:

* Scans Laravel models
* Detects all Eloquent relationships
* Optionally inspects database schema
* Generates a structured JSON graph file
* Stores JSON at:

  ```
  storage/app/laravel-model-graph.json
  ```
* Serves a React SPA at:

  ```
  /graph
  ```
* Serves API endpoint at:

  ```
  /api/v1/graph/data
  ```
* Works in:

  * local
  * testing
  * production (ONLY if enabled via config/.env)

---

# 🧱 REQUIRED PACKAGE STRUCTURE

Generate the following structure:

```
src/
  LaravelModelGraphServiceProvider.php
  Http/
    Controllers/
      GraphController.php
  Console/
    GenerateModelGraphCommand.php
  Services/
    GraphBuilderService.php
  Support/
    ModelScanner.php
    RelationshipResolver.php
    SchemaInspector.php

config/
  model-graph.php

routes/
  web.php
  api.php

resources/
  views/
    app.blade.php
  dist/
    (React compiled assets already exist here)
```

---

# ⚙️ ENV + CONFIG REQUIREMENTS

Create config file: `config/model-graph.php`

It must support:

```php
return [

    'enabled' => env('MODEL_GRAPH_ENABLED', true),

    'auto_generate' => env('MODEL_GRAPH_AUTO_GENERATE', true),

    'environment' => ['local', 'testing'],

    'allow_production' => env('MODEL_GRAPH_ALLOW_PRODUCTION', false),

    'middleware' => ['web'],

    'api_middleware' => ['web'],

    'storage_path' => storage_path('app/laravel-model-graph.json'),

    'scan' => [
        'models_path' => app_path('Models'),
        'use_reflection' => true,
        'use_schema_inspection' => true,
    ],

];
```

Production rules:

* Package runs in local/testing automatically
* In production:

  * Only runs if `MODEL_GRAPH_ALLOW_PRODUCTION=true`
  * AND `MODEL_GRAPH_ENABLED=true`

---

# 🧠 CORE FEATURES TO IMPLEMENT

---

# 1️⃣ Service Provider

Create `LaravelModelGraphServiceProvider` that:

* Publishes config
* Registers routes
* Registers command
* Uses composer auto-discovery
* Conditionally boots only when:

  * enabled
  * environment allowed

Route registration must respect middleware config.

---

# 2️⃣ Console Command

Create:

```
php artisan model-graph:generate
```

Responsibilities:

* Scan models
* Build graph JSON
* Store at configured storage path
* Overwrite existing file
* Output success message
* Handle exceptions gracefully

---

# 3️⃣ GraphBuilderService

Main orchestrator.

Responsibilities:

* Use ModelScanner
* Use RelationshipResolver
* Use SchemaInspector (if enabled)
* Merge all data
* Build final JSON structure
* Return structured array

---

# 4️⃣ ModelScanner

Responsibilities:

* Scan `app/Models`
* Support custom namespace detection
* Use ReflectionClass
* Only include classes extending `Illuminate\Database\Eloquent\Model`
* Return list of model class names

---

# 5️⃣ RelationshipResolver

Must support ALL Laravel relationships:

* hasOne
* hasMany
* belongsTo
* belongsToMany
* morphOne
* morphMany
* morphTo
* morphToMany
* morphedByMany
* hasManyThrough
* hasOneThrough

Implementation requirements:

* Use reflection to inspect public methods
* Detect return type instances of:

  * Illuminate\Database\Eloquent\Relations\Relation
* Safely instantiate model without triggering DB calls
* Extract:

  * related model
  * relationship type
  * foreign key (if possible)
  * pivot table (if applicable)
* Prevent infinite recursion
* Detect circular references

Return relationship graph array.

---

# 6️⃣ SchemaInspector

If enabled in config:

* Use DB connection
* Use Schema facade
* Extract:

  * table name
  * columns
  * column types
  * primary keys
* Merge into graph nodes

Must be optional and configurable.

---

# 📊 JSON STRUCTURE (CRITICAL)

Final JSON MUST follow this structure:

```json
{
  "meta": {
    "generated_at": "...",
    "environment": "...",
    "model_count": 0,
    "relationship_count": 0
  },
  "nodes": [
    {
      "id": "App\\Models\\User",
      "name": "User",
      "table": "users",
      "columns": [],
      "relationships_count": 0
    }
  ],
  "edges": [
    {
      "id": "user_has_many_posts",
      "source": "App\\Models\\User",
      "target": "App\\Models\\Post",
      "type": "hasMany",
      "label": "posts"
    }
  ]
}
```

This JSON will be consumed directly by React Flow.

---

# 🌐 ROUTES

---

## Web Route

```
GET /graph
```

* Loads Blade view
* Blade loads compiled React assets from:

  ```
  resources/dist/
  ```
* Publicly accessible in local
* Middleware configurable
* Protected in production unless enabled

---

## API Route

```
GET /api/v1/graph/data
```

Behavior:

If file exists:

* Return JSON

If file does not exist:

* If auto_generate=true → generate then return
* Else → return error message

Must be performant and avoid regeneration if not needed.

---

# 🖥 Blade View

Create `resources/views/app.blade.php`

Requirements:

* Simple HTML wrapper
* Root div id="app"
* Dynamically load JS/CSS from dist folder
* Do NOT use Vite
* Use asset() helper or proper path resolution

---

# 🔐 MIDDLEWARE + ACCESS CONTROL

Behavior rules:

* Publicly accessible in local
* In production:

  * Only accessible if config allows
* Middleware fully configurable via config
* Use route group middleware

---

# ⚡ PERFORMANCE RULES

* Do not scan models on every request
* Only generate via:

  * artisan command
  * or first request (if auto_generate enabled)
* Use file storage
* Avoid database writes
* Avoid event listeners
* Avoid runtime observers

---

# 📦 COMPOSER REQUIREMENTS

Ensure composer.json contains:

```json
"extra": {
  "laravel": {
    "providers": [
      "Matakltm\\LaravelModelGraph\\LaravelModelGraphServiceProvider"
    ]
  }
}
```

Mark package as:

```
--dev recommended
```

---

# 🧪 ERROR HANDLING

Must gracefully handle:

* Invalid model classes
* Broken relationships
* Missing tables
* Reflection errors
* Schema failures

Log errors but continue processing other models.

---

# 🧩 CODE QUALITY REQUIREMENTS

* Laravel 10+ compatible
* PHP 8.1+
* Strict types
* Clean architecture
* SOLID principles
* No unnecessary dependencies
* Clear docblocks
* Production-grade structure

---

# 🚀 FINAL GOAL

Deliver a fully working Laravel package backend that:

* Generates model relationship graph JSON
* Supports all Eloquent relationships
* Uses both reflection + schema inspection (configurable)
* Runs safely in dev and optionally production
* Serves React SPA cleanly
* Is performant
* Is extensible
* Is ecosystem-ready

Do not generate explanations.
Generate full implementation code for all required files.
