# %activity_entity%

## FIELDS

### %activity_targets_association%

Records associated with the %activity_entity_name% record.

## SUBRESOURCES

### %activity_targets_association%

#### get_subresource

Retrieve records associated with a specific %activity_entity_name% record.

#### get_relationship

Retrieve the IDs of records associated with a specific %activity_entity_name% record.

#### add_relationship

Associate records with a specific %activity_entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%activity_target_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace records that are associated with a specific %activity_entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%activity_target_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove an association between records and a specific %activity_entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%activity_target_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}
