# Test\User

## ACTIONS

### update

Update a new user.

{@request:json_api}
Example 1:

```JSON
{
  "data": {
    "type": "users",
    "id": "123",
    "attributes": {
      "name": "Some User"
    }
  }
}
```
{@/request}

{@request:json_api}
Example 2:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "123",
    "attributes": {
      "externalId": "ext_1",
      "name": "Some Org"
    },
    "relationships": {
      "organization": {
        "data": {
          "type": "users",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

{@request:json_api}
Example 3:

```JSON
{
  "data": {
    "type": "users",
    "id": "123",
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

{@request:json_api}
Example 4:

```JSON
{
  "data": {
    "type": "users",
    "id": "123",
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

{@request:json_api&another}
Example 5:

```JSON
{
  "data": {
    "type": "users",
    "id": "123",
    "attributes": {
      "externalId": "ext_1",
      "name": "Some User"
    }
  }
}
```
{@/request}
