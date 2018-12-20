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
   "data":{  
      "type":"notes",
      "attributes":{  
         "message":"<p>test note message</p>"
      },
      "relationships":{  
         "activityTargets":{  
            "data":[  
               {  
                  "type":"accounts",
                  "id":"7"
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

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"notes",
      "id":"2",
      "attributes":{  
         "message":"New message"
      },
      "relationships":{  
         "owner":{  
            "data":{  
               "type":"users",
               "id":"1"
            }
         },
         "organization":{  
            "data":{  
               "type":"organizations",
               "id":"1"
            }
         },
         "activityTargets":{  
            "data":[  
               {  
                  "type":"accounts",
                  "id":"7"
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

### id

#### update

{@inheritdoc}

**The required field**

### message

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### activityTargets

A records to which the note record associated with.

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### attachment

#### get_subresource

Retrieve the record of the attachment uploaded with a specific note.

#### get_relationship

Retrieve the ID of the file attached to a specific note.

#### update_relationship

Replace the file attached to a specific note.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "files",
    "id": "6"
  }
}
```
{@/request}

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

Retrieve the ID of a user who is the owner of a specific note record

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

### activityTargets

#### get_subresource

Retrieve records to which the note associated.

#### get_relationship

Retrieve the IDs of records to which the note associated.

#### add_relationship

Associate records with the note.

#### update_relationship

Completely replace association between records and the note.

#### delete_relationship

Delete association between records and the note.
