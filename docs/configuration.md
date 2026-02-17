# Configuration

The **Laravel Model Graph** package is highly configurable. You can customize its behavior by modifying the `config/model-graph.php` file.

## General Options

### `enabled`
- **Type**: `boolean`
- **Default**: `env('MODEL_GRAPH_ENABLED', true)`
- Controls whether the model graph generation and UI are enabled.

### `auto_generate`
- **Type**: `boolean`
- **Default**: `env('MODEL_GRAPH_AUTO_GENERATE', true)`
- If enabled, the package will automatically generate the graph data if it does not exist when requested via the API.

---

## Security & Access

### `environments`
- **Type**: `array`
- **Default**: `['local', 'testing']`
- The environments where the package is allowed to run.

### `allow_production`
- **Type**: `boolean`
- **Default**: `env('MODEL_GRAPH_ALLOW_PRODUCTION', false)`
- Whether to allow the package to run in production environments. **Use with caution.**

### `middleware`
- **Type**: `array`
- **Default**: `['web']`
- The middleware that should be applied to the web routes (`/graph`).

### `api_middleware`
- **Type**: `array`
- **Default**: `['web']`
- The middleware that should be applied to the API routes (`/api/v1/graph/data`).

---

## Storage & Cache

### `storage_path`
- **Type**: `string`
- **Default**: `storage_path('app/laravel-model-graph.json')`
- The path where the generated graph JSON file will be stored.

### `cache.enabled`
- **Type**: `boolean`
- **Default**: `true`
- Enable caching of the generated graph data.

### `cache.ttl`
- **Type**: `integer`
- **Default**: `3600`
- Cache time-to-live in seconds.

---

## Scanning & Relationships

### `scan.models_paths`
- **Type**: `array`
- **Default**: `[app_path('Models')]`
- List of directories to scan for Eloquent models.

### `scan.use_reflection`
- **Type**: `boolean`
- **Default**: `true`
- Use reflection to discover relationships.

### `scan.use_schema_inspection`
- **Type**: `boolean`
- **Default**: `true`
- Use database schema inspection to extract column details.

### `relationships.types`
- **Type**: `array`
- **Default**: All standard Eloquent relationships.
- List of relationship types to detect.

---

## JSON Options

### `json_options`
- **Type**: `integer`
- **Default**: `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES`
- Options passed to `json_encode` when generating the graph data.
