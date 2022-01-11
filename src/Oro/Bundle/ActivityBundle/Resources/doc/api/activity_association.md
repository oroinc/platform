# %activity_target_entity%

## FIELDS

### %activity_association%

The %activity_entity_plural_name% associated with the %entity_name% record.

## SUBRESOURCES

### %activity_association%

#### get_subresource

Retrieve the records of the %activity_entity_plural_name% associated with a specific %entity_name% record.

#### get_relationship

Retrieve the IDs of the %activity_entity_plural_name% associated with a specific %entity_name% record.

#### add_relationship

Associate the %activity_entity_plural_name% with a specific %entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%activity_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the %activity_entity_plural_name% that are associated with a specific %entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%activity_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove an association between the %activity_entity_plural_name% and a specific %entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%activity_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}
