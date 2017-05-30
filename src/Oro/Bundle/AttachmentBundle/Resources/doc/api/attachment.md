# Oro\Bundle\AttachmentBundle\Entity\Attachment

## ACTIONS  

### get

Retrieve a specific attachment record.

{@inheritdoc}

### get_list

Retrieve a collection of attachment records.

{@inheritdoc}

### create

Create a new attachment record.
The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

`</api/attachments>`

```JSON
{  
   "data":{  
      "type":"attachments",
      "attributes":{  
         "comment":"Account's background"
      },
      "relationships":{  
         "organization":{  
            "data":{  
               "type":"organizations",
               "id":"1"
            }
         },
         "file":{  
            "data":{  
               "type":"files",
               "id":"1"
            }
         },
         "owner":{  
            "data":{  
               "type":"users",
               "id":"1"
            }
         },
         "target":{  
            "data":{  
               "type":"accounts",
               "id":"1"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific attachment record.

{@inheritdoc}

{@request:json_api}
Example:

`</api/attachments/3>`

```JSON
{  
   "data":{  
      "type":"attachments",
      "id":"3",
      "attributes":{  
         "comment":"Account's background"
      },
      "relationships":{  
         "organization":{  
            "data":{  
               "type":"organizations",
               "id":"1"
            }
         },
         "file":{  
            "data":{  
               "type":"files",
               "id":"1"
            }
         },
         "owner":{  
            "data":{  
               "type":"users",
               "id":"1"
            }
         },
         "target":{  
            "data":{  
               "type":"accounts",
               "id":"1"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific attachment record.

{@inheritdoc}

### delete_list

Delete a collection of attachment records.
The list of records that will be deleted, could be limited by filters.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### file

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### target

A record to which the attachment record belongs to.

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### file

#### get_subresource

Retrieve the file record assigned to a specific attachment record.

#### get_relationship

Retrieves the ID of the file assigned to a specific attachment record.

#### update_relationship

Replace the file assigned to a specific attachment record.

{@request:json_api}
Example:

`</api/attachments/1/relationships/file>`

```JSON
{
  "data": {
    "type": "files",
    "id": "11"
  }
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific attachment record belongs to.

#### get_relationship

Retrieve the ID of the organization record that a specific attachment record belongs to.

#### update_relationship

Replace the organization a specific attachment record belongs to.

{@request:json_api}
Example:

`</api/attachments/1/relationships/organization>`

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

Retrieve the record of the user who is the owner of a specific attachment record

#### get_relationship

Retrieve the ID of a user who is the owner of a specific attachment record

#### update_relationship

Replace the owner of a specific attachment record.

{@request:json_api}
Example:

`</api/attachments/1/relationships/owner>`

```JSON
{
  "data": {
    "type": "users",
    "id": "8"
  }
}
```
{@/request}

### target

#### get_subresource

Get full information about a record to which the attachment belongs to.

#### get_relationship

Get a record to which the attachment belongs to.

#### update_relationship

Replace a record to which the attachment belongs to.

{@request:json_api}
Example:

`</api/attachments/1/relationships/target>`

```JSON
{
  "data": {
    "type": "accounts",
    "id": "25"
  }
}
```
{@/request}
