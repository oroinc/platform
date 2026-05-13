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
    "id": "1",
    "attributes": {
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
