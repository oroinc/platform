# Oro\Bundle\CommentBundle\Entity\Comment

## ACTIONS  

### get

Retrieve a specific comment record.

{@inheritdoc}

### get_list

Retrieve a collection of comment records.

{@inheritdoc}

### create

Create a new comment record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "comments",
      "attributes": {
         "message": "<p>test contact</p>"
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
         "attachment": {
            "data": {
               "type": "files",
               "id": "1"
            }
         },
         "target": {
            "data": {
               "type": "emails",
               "id": "123"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific comment record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "comments",
      "id": "11",
      "attributes": {
         "message": "<p>test contact</p>"
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
         "attachment": {
            "data": {
               "type": "files",
               "id": "1"
            }
         },
         "target": {
            "data": {
               "type": "emails",
               "id": "123"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific comment record.

{@inheritdoc}

### delete_list

Delete a collection of comment records.

{@inheritdoc}

## FIELDS

### message

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### target

A record that the comment was made on.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

## SUBRESOURCES

### attachment

#### get_subresource

Retrieve the record of the attachment uploaded with a specific comment.

#### get_relationship

Retrieve the ID of the file attached to a specific comment.

#### update_relationship

Replace the file attached to a specific comment.

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

### organization

#### get_subresource

Retrieve the record of the organization that a specific comment belongs to.

#### get_relationship

Retrieve the ID of the organization record that a specific comment belongs to.

#### update_relationship

Replace the organization that a specific comment belongs to.

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

Retrieve the record of the user who is the owner of a specific comment record.

#### get_relationship

Retrieve the ID of the user who is the owner of a specific comment record.

#### update_relationship

Replace the owner of a specific comment record.

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

### target

#### get_subresource

Retrieve an entity record that the comment was made on.

#### get_relationship

Retrieve the ID of an entity record that the comment was made on.

#### update_relationship

Replace an entity record that the comment was made on.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "calls",
    "id": "48"
  }
}
```
{@/request}

### updatedBy

#### get_subresource

Retrieve the record of the user who last updated a specific comment.

#### get_relationship

Retrieve the ID of the user who last updated a specific comment.

#### update_relationship

Replace the user who last updated a specific comment.

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
