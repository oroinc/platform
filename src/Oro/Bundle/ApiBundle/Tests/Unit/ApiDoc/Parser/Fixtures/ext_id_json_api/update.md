# Test\User

## ACTIONS

### update

Update a new user.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
    "id": "123",
    "attributes": {
      "externalId": "ext_1",
      "name": "Some User"
    },
    "relationships": {
      "organization": {
        "data": {
          "type": "organizations",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}
