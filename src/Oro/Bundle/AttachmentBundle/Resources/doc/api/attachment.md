# Oro\Bundle\AttachmentBundle\Entity\Attachment

## ACTIONS  

### get

Retrieve a specific attachment record.

{@inheritdoc}

### get_list

Retrieve a set of attachment records.

{@inheritdoc}

### create

Create a new attachment record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "attachments",
      "attributes": {
         "comment": "Account's background"
      },
      "relationships": {
         "organization": {
            "data": {
               "type": "organizations",
               "id": "1"
            }
         },
         "file": {
            "data": {
               "type": "files",
               "id": "attached_file"
            }
         },
         "owner": {
            "data": {
               "type": "users",
               "id": "1"
            }
         },
         "target": {
            "data": {
               "type": "accounts",
               "id": "1"
            }
         }
      }
   },
   "included": [
     {
       "type": "files",
       "id": "attached_file",
       "attributes": {
         "mimeType": "image/jpeg",
         "originalFilename": "onedot.jpg",
         "fileSize": 631,
         "content": "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/2Q=="
       },
       "relationships": {
         "owner": {
           "data": {
             "type": "users",
             "id": "1"
           }
         }
       }
     }
   ]
}
```
{@/request}

### update

Edit a specific attachment record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "attachments",
      "id": "3",
      "attributes": {
         "comment": "Account's background"
      },
      "relationships": {
         "organization": {
            "data": {
               "type": "organizations",
               "id": "1"
            }
         },
         "owner": {
            "data": {
               "type": "users",
               "id": "1"
            }
         },
         "target": {
            "data": {
               "type": "accounts",
               "id": "1"
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

Delete a set of attachment records.

{@inheritdoc}

## FIELDS

### file

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### target

A record which the attachment record belongs to.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

## SUBRESOURCES

### file

#### get_subresource

Retrieve the file record assigned to a specific attachment record.

#### get_relationship

Retrieve the ID of the file assigned to a specific attachment record.

### organization

#### get_subresource

Retrieve a record of the organization that a specific attachment record belongs to.

#### get_relationship

Retrieve the ID of the organization record that a specific attachment record belongs to.

#### update_relationship

Replace the organization that a specific attachment record belongs to.

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

Retrieve a record of a user who is the owner of a specific attachment record.

#### get_relationship

Retrieve the ID of a user who is the owner of a specific attachment record.

#### update_relationship

Replace the owner of a specific attachment record.

{@request:json_api}
Example:

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

Retrieve the record which the attachment belongs to.

#### get_relationship

Retrieve the ID of a record which the attachment belongs to.

#### update_relationship

Replace the record which the attachment belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "accounts",
    "id": "25"
  }
}
```
{@/request}
