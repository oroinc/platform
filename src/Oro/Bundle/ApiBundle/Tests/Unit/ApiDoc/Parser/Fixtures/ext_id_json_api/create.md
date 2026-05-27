# Test\User

## ACTIONS

### create

Create a new user.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
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
