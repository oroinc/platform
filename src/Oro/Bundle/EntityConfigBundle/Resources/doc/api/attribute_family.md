# Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily

## ACTIONS

### get

Retrieve a specific attribute family record.

{@inheritdoc}

### get_list

Retrieve a collection of attribute family records.

{@inheritdoc}

### update

Edit a specific attribute family record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "attributefamilies",
    "id": "1",
    "attributes": {
      "code": "default_family",
      "entityClass": "Oro\\Bundle\\ProductBundle\\Entity\\Product",
      "isEnabled": true
    },
    "relationships": {
      "labels": {
        "data": [
          {
            "type": "localizedfallbackvalues",
            "id": "5"
          }
        ]
      },    
      "image": {
        "data": {
          "type": "files",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

## FIELDS

### code

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

## SUBRESOURCES

### labels

#### get_subresource

Retrieve a record of label assigned to a specific attribute family record.

#### get_relationship

Retrieve IDs of label records assigned to a specific attribute family record.

#### update_relationship

Replace the list of label records assigned to a specific attribute family record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "5"
    }
  ]
}
```
{@/request}

#### add_relationship

Set label records for a specific attribute family record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "5"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove label records from a specific attribute family record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "localizedfallbackvalues",
      "id": "5"
    }
  ]
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific attribute family record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific attribute family record will belong to.

#### update_relationship

Replace the organization a specific attribute family record belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}

### owner

#### get_subresource

Retrieve the record of the user who is an owner of a specific attribute family record.

#### get_relationship

Retrieve the ID of the user who is an owner of a specific attribute family record.

#### update_relationship

Replace the owner of a specific attribute family record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
    "id": "1"
  }
}
```
{@/request}

### image

#### get_subresource

Retrieve a record of image assigned to a specific attribute family record.

#### get_relationship

Retrieve ID of image records assigned to a specific attribute family record.

#### update_relationship

Replace image assigned to a specific attribute family record

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "files",
    "id": "1"
  }
}
```
{@/request}
