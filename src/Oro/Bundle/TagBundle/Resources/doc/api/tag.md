# Oro\Bundle\TagBundle\Entity\Tag

## ACTIONS

### get

Retrieve a specific tag record.

{@inheritdoc}

### get_list

Retrieve a collection of tag records.

{@inheritdoc}

### create

Create a new tag record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "tags",
      "attributes": {
        "name": "Commercial"
      },
      "relationships": {
        "taxonomy": {
          "data": {
            "type": "taxonomies",
            "id": "1"
          }
        }
      }
   }
}
```
{@/request}

### update

Edit a specific tag record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "tags",
      "id": "1",
      "attributes": {
        "name": "Commercial"
      },
      "relationships": {
        "taxonomy": {
          "data": {
            "type": "taxonomies",
            "id": "1"
          }
        }
      }
   }
}
```
{@/request}

### delete

Delete a specific tag record.

{@inheritdoc}

### delete_list

Delete a collection of tag records.

{@inheritdoc}

## FIELDS

### name

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### entities

Entities that are marked by the tag.

## SUBRESOURCES

### organization

#### get_subresource

Retrieve a record of an organization that a specific tag record belongs to.

#### get_relationship

Retrieve an ID of the organization that a specific tag record belongs to.

#### update_relationship

Replace the organization that a specific tag belongs to.

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

Retrieve a record of the user who is the owner of a specific tag record.

#### get_relationship

Retrieve an ID of the user who is the owner of a specific tag record.

#### update_relationship

Replace the user who is the owner of a specific tag record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
    "id": "2"
  }
}
```
{@/request}

### taxonomy

#### get_subresource

Retrieve a record of the taxonomic unit associated with a specific tag record.

#### get_relationship

Retrieve an ID of the taxonomic unit associated with a specific tag record.

#### update_relationship

Replace the taxonomic unit associated with a specific tag record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxonomies",
    "id": "1"
  }
}
```
{@/request}

### entities

#### get_subresource

Retrieve the records of the entities that are marked by the tag.

#### get_relationship

Retrieve the IDs of the entities that are marked by the tag.

#### add_relationship

Mark entities by the tag.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace entities that are marked by the tag.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    }
  ]
}
```
{@/request}

#### delete_relationship

Unmark entities by the tag.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    }
  ]
}
```
{@/request}
