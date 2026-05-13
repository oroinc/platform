# Test\Product

## ACTIONS

### update

Update a new product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "TEST_SKU",
    "attributes": {
      "name": "Some Product"
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
