# JSON Output Format

The **Laravel Model Graph** package generates a JSON file that represents the structure of your Eloquent models and their relationships. This document provides a detailed breakdown of the JSON structure.

## Root Structure

The root object contains three main sections:

| Key | Type | Description |
| :--- | :--- | :--- |
| `meta` | `object` | Metadata about the generation process. |
| `nodes` | `array` | A list of models found in the application. |
| `edges` | `array` | A list of relationships between models. |

---

## Meta Object

| Key | Type | Description |
| :--- | :--- | :--- |
| `generated_at` | `string` | ISO 8601 timestamp of when the graph was generated. |
| `environment` | `string` | The environment where the graph was generated (e.g., `local`). |
| `model_count` | `integer` | The total number of models (nodes) in the graph. |
| `relationship_count` | `integer` | The total number of relationships (edges) in the graph. |

---

## Nodes (Models)

Each node represents an Eloquent model.

| Key | Type | Description |
| :--- | :--- | :--- |
| `id` | `string` | The fully qualified class name (FQCN) of the model. |
| `name` | `string` | The short name of the class. |
| `table` | `string` | The database table associated with the model. |
| `columns` | `array` | A list of column details (if schema inspection is enabled). |
| `relationships_count` | `integer` | The number of relationships defined in this model. |

### Column Object

If schema inspection is enabled, the `columns` array contains objects with:

- `name`: Column name.
- `type`: Column type (e.g., `integer`, `string`).
- `nullable`: Boolean indicating if the column is nullable.
- `default`: Default value.
- `indexes`: Array of indexes this column belongs to.

---

## Edges (Relationships)

Each edge represents a relationship between two models.

| Key | Type | Description |
| :--- | :--- | :--- |
| `id` | `string` | A unique identifier for the edge. |
| `source` | `string` | The FQCN of the model defining the relationship. |
| `target` | `string` | The FQCN of the related model. |
| `type` | `string` | The type of relationship (e.g., `HasMany`, `BelongsTo`). |
| `label` | `string` | The name of the relationship method. |
| `metadata` | `object` | Additional details like foreign keys, pivot tables, etc. |

### Metadata Object

Depending on the relationship type, the metadata may include:

- `foreign_key`: The foreign key used in the relationship.
- `local_key` / `owner_key`: The local or owner key.
- `pivot_table`: The name of the pivot table (for `BelongsToMany`).
- `foreign_pivot_key` / `related_pivot_key`: Pivot keys.
- `morph_type`: The morph type column (for polymorphic relationships).

---

## Example JSON

```json
{
  "meta": {
    "generated_at": "2024-03-20T10:00:00+00:00",
    "environment": "local",
    "model_count": 2,
    "relationship_count": 1
  },
  "nodes": [
    {
      "id": "App\\Models\\User",
      "name": "User",
      "table": "users",
      "columns": [
        {
          "name": "id",
          "type": "bigint",
          "nullable": false,
          "default": null,
          "indexes": [
            {
              "name": "users_id_primary",
              "columns": ["id"],
              "type": "primary"
            }
          ]
        },
        {
            "name": "name",
            "type": "varchar",
            "nullable": false,
            "default": null,
            "indexes": []
        }
      ],
      "relationships_count": 1
    },
    {
      "id": "App\\Models\\Post",
      "name": "Post",
      "table": "posts",
      "columns": [...],
      "relationships_count": 0
    }
  ],
  "edges": [
    {
      "id": "user_posts_post",
      "source": "App\\Models\\User",
      "target": "App\\Models\\Post",
      "type": "HasMany",
      "label": "posts",
      "metadata": {
        "name": "posts",
        "type": "HasMany",
        "related": "App\\Models\\Post",
        "foreign_key": "user_id",
        "local_key": "id"
      }
    }
  ]
}
```
