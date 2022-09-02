# %taggable_entity%

## FIELDS

### %tags_association%

The tags associated with the %entity_name% record.

## SUBRESOURCES

### %tags_association%

#### get_subresource

Retrieve the tags associated with a specific %entity_name% record.

#### get_relationship

Retrieve the IDs of the tags associated with a specific %entity_name% record.

#### add_relationship

Associate the tags with a specific %entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%tag_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the tags that are associated with a specific %entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%tag_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove an association between the tags and a specific %entity_name% record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "%tag_entity_type%",
      "id": "1"
    }
  ]
}
```
{@/request}
