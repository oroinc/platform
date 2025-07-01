# Oro\Bundle\NoteBundle\Entity\Note

## ACTIONS  

### get

Retrieve a specific note record.

{@inheritdoc}

### get_list

Retrieve a collection of note records.

{@inheritdoc}

### create

Create a new note record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "notes",
      "attributes": {
         "message": "<p>test note message</p>"
      },
      "relationships": {
         "activityTargets": {
            "data": [
               {
                  "type": "accounts",
                  "id": "7"
               }
            ]
         }
      }
   }
}
```
{@/request}

### update

Edit a specific note record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "notes",
      "id": "2",
      "attributes": {
         "message": "New message"
      },
      "relationships": {
         "owner": {
            "data": {
               "type": "users",
               "id": "1"
            }
         },
         "organization": {
            "data": {
               "type": "organizations",
               "id": "1"
            }
         },
         "activityTargets": {
            "data": [
               {
                  "type": "accounts",
                  "id": "7"
               }
            ]
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific note record.

{@inheritdoc}

### delete_list

Delete a collection of note records.

{@inheritdoc}

## FIELDS

### message

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### activityTargets

#### create

{@inheritdoc}

**The required field.**

## SUBRESOURCES

### attachment

#### get_subresource

Retrieve the record of the attachment uploaded with a specific note.

#### get_relationship

Retrieve the ID of the file attached to a specific note.

### organization

#### get_subresource

Retrieve the record of the organization a specific note record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific note record will belong to.

#### update_relationship

Replace the organization a specific note record belongs to.

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

Retrieve the record of the user who is the owner of a specific note record.

#### get_relationship

Retrieve the ID of a user who is the owner of a specific note record.

#### update_relationship

Replace the owner of a specific note record.

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

### updatedBy

#### get_subresource

Retrieve the record of the user who updated a specific note record.

#### get_relationship

Retrieve the ID of the user who updated a specific note record.

#### update_relationship

Replace the user who updated a specific note record.

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
