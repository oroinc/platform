# Oro\Bundle\TagBundle\Entity\Taxonomy

## ACTIONS  

### get

Retrieve a specific taxonomic unit record.

{@inheritdoc}

### get_list

Retrieve a collection of taxonomic unit records.

{@inheritdoc}

### create

Create a new taxonomic unit record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "taxonomies",
      "attributes": {
        "name": "Commerce",
        "backgroundColor": "#FF0000"
      }
   }
}
```
{@/request}

### update

Edit a specific taxonomic unit record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "taxonomies",
      "id": "1",
      "attributes": {
         "name": "Commerce",
         "backgroundColor": "#FF0000"
      }
   }
}
```
{@/request}

### delete

Delete a specific taxonomic unit record.

{@inheritdoc}

### delete_list

Delete a collection of taxonomic unit records.

{@inheritdoc}

## FIELDS

### name

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

## SUBRESOURCES

### organization

#### get_subresource

Retrieve a record of an organization that a specific taxonomic unit record belongs to.

#### get_relationship

Retrieve an ID of the organization that a specific taxonomic unit record belongs to.

#### update_relationship

Replace the organization that a specific taxonomic unit belongs to.

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

Retrieve a record of the user who is the owner of a specific taxonomic unit record.

#### get_relationship

Retrieve an ID of the user who is the owner of a specific taxonomic unit record.

#### update_relationship

Replace the user who is the owner of a specific taxonomic unit record.

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
