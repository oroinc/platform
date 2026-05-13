# Test\Product

## ACTIONS

### create

Create a new product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "attributes": {
      "sku": "SKU_1",
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
