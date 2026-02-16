<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Model Graph Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether the model graph generation and UI are enabled.
    |
    */
    'enabled' => env('MODEL_GRAPH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Auto Generate
    |--------------------------------------------------------------------------
    |
    | If enabled, the package will automatically generate the graph data if
    | it does not exist when requested via the API.
    |
    */
    'auto_generate' => env('MODEL_GRAPH_AUTO_GENERATE', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | The environments where the package is allowed to run.
    |
    */
    'environments' => explode(',', env('MODEL_GRAPH_ENVIRONMENTS', 'local,testing')),

    /*
    |--------------------------------------------------------------------------
    | Allow Production
    |--------------------------------------------------------------------------
    |
    | Whether to allow the package to run in production environments.
    |
    */
    'allow_production' => env('MODEL_GRAPH_ALLOW_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that should be applied to the web routes.
    |
    */
    'middleware' => explode(',', env('MODEL_GRAPH_MIDDLEWARE', 'web')),

    /*
    |--------------------------------------------------------------------------
    | API Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that should be applied to the API routes.
    |
    */
    'api_middleware' => explode(',', env('MODEL_GRAPH_API_MIDDLEWARE', 'web')),

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | The path where the generated graph JSON file will be stored.
    |
    */
    'storage_path' => env('MODEL_GRAPH_STORAGE_PATH', storage_path('app/laravel-model-graph.json')),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching the generated graph.
    |
    */
    'cache' => [
        'enabled' => env('MODEL_GRAPH_CACHE_ENABLED', true),
        'key' => env('MODEL_GRAPH_CACHE_KEY', 'laravel-model-graph-data'),
        'ttl' => (int) env('MODEL_GRAPH_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Relationship Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for relationship detection and display.
    |
    */
    'relationships' => [
        'types' => explode(',', env('MODEL_GRAPH_RELATIONSHIP_TYPES', 'HasOne,HasMany,BelongsTo,BelongsToMany,MorphTo,MorphOne,MorphMany,MorphToMany,MorphedByMany,HasOneThrough,HasManyThrough')),
        'max_depth' => (int) env('MODEL_GRAPH_MAX_DEPTH', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Column Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for which columns to include in the graph.
    |
    */
    'include_columns' => (bool) env('MODEL_GRAPH_INCLUDE_COLUMNS', true),

    /*
    |--------------------------------------------------------------------------
    | JSON Options
    |--------------------------------------------------------------------------
    |
    | Options passed to json_encode when generating the graph data.
    |
    */
    'json_options' => (int) env('MODEL_GRAPH_JSON_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),

    /*
    |--------------------------------------------------------------------------
    | Scan Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for scanning models and schema inspection.
    |
    */
    'scan' => [
        'models_paths' => [
            app_path('Models'),
        ],
        'ignore_models' => [],
        'include_only' => [],
        'use_reflection' => true,
        'use_schema_inspection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | The duration in seconds to cache the discovered models.
    | Set to 0 to disable caching.
    |
    */
    'cache_duration' => env('MODEL_GRAPH_CACHE_DURATION', 3600),

];
