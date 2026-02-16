# TODO Checklist: Laravel Model Graph Implementation

This checklist tracks the implementation of the Laravel Model Graph package components.

## 1. Core Services
- [ ] **ModelScannerService**: Implement scanning of `app/Models` using Reflection.
- [ ] **RelationshipResolverService**: Implement detection of all Eloquent relationships (hasOne, hasMany, belongsTo, etc.).
- [ ] **SchemaInspectorService**: Implement database schema extraction using Schema facade.
- [ ] **GraphBuilderService**: Orchestrate all services to build the final JSON structure.

## 2. Console Commands
- [ ] **GenerateGraphCommand**: Implement the logic to trigger graph generation and store JSON at the configured path.

## 3. Web & API Layer
- [ ] **GraphController**: Implement web route to serve the Blade view.
- [ ] **GraphApiController**: Implement API endpoint to serve the generated JSON data.
- [ ] **Routes**: Finalize `routes/web.php` and `routes/api.php` with correct controllers.

## 4. Resources
- [ ] **graph.blade.php**: Ensure correct loading of compiled React assets.
- [ ] **React SPA**: Implement/Integrate the React Flow SPA in `resources/dist/`.

## 5. Configuration & Provider
- [ ] **LaravelModelGraphServiceProvider**: Implement conditional booting, middleware application, and resource publishing.
- [ ] **Config**: Ensure all environment variables and configuration options are respected.

## 6. Testing & Quality
- [ ] **Unit Tests**: Complete implementation of unit tests for all services.
- [ ] **Feature Tests**: Implement end-to-end tests for command and API endpoints.
- [ ] **Static Analysis**: Ensure 100% compatibility with PHPStan (Level Max) and Rector.
- [ ] **Type Coverage**: Maintain 100% type coverage.

## 7. Documentation
- [ ] Update README.md with installation and usage instructions.
- [ ] Document JSON structure for external consumption.
