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
    'environment' => ['local', 'testing'],

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
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | API Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that should be applied to the API routes.
    |
    */
    'api_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | The path where the generated graph JSON file will be stored.
    |
    */
    'storage_path' => storage_path('app/laravel-model-graph.json'),

    /*
    |--------------------------------------------------------------------------
    | Scan Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for scanning models and schema inspection.
    |
    */
    'scan' => [
        'models_path' => app_path('Models'),
        'use_reflection' => true,
        'use_schema_inspection' => true,
    ],

];
