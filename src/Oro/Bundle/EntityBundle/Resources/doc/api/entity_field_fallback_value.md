# Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue

## ACTIONS

### get

Retrieve a specific entity field fallback value record.

{@inheritdoc}

### get_list

Retrieve a collection of entity field fallback value records.

{@inheritdoc}

### create

Create a new entity field fallback value record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "entityfieldfallbackvalues",
    "attributes": {
      "fallback": null,
      "scalarValue": "test"
    }
  }
}
```
{@/request}

### update

Edit a specific entity field fallback value record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "entityfieldfallbackvalues",
      "id": "1",
      "attributes": {
         "fallback": null,
         "scalarValue": "test"
      }
   }
}
```
{@/request}

## FIELDS

### fallback

The value of the fallback. Possible values: `systemConfig` or `null`.

### arrayValue

The array value of the entity field.

### scalarValue

The scalar value for the entity field.
