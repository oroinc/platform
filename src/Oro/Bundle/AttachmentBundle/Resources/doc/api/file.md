# Oro\Bundle\AttachmentBundle\Entity\File

## ACTIONS  

### get

Retrieve a specific file record.

{@inheritdoc}

### create

Create a new file record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "files",
      "attributes": {
         "originalFilename": "onedot.jpg",
         "fileSize": 631,
         "content": "/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/2Q==",
         "parentFieldName": "avatar"
      },
      "relationships": {
         "owner": {
            "data": {
               "type": "users",
               "id": "1"
            }
         },
         "parent": {
           "data": {
             "type": "users",
             "id": "10"
           }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific file record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
   "data": {
      "type": "files",
      "id": "2",
      "attributes": {
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
}
```
{@/request}

### delete

Delete a specific file record.

{@inheritdoc}

### delete_list

Delete a set of file records.

{@inheritdoc}

## FIELDS

### uuid

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### mimeType

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored. The MIME type is detected from the file content.**

### originalFilename

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### content

The content of the file as a Base64 encoded string. Either `externalUrl` or `content` must be specified, but not both.

The MIME type and the size of the content must be allowed by the configuration of the parent entity field or, for a file of an attachment, by the attachment configuration of the attachment target entity. For a file that has no parent entity, the system-wide upload settings apply: the allowed MIME types and the maximum file size. Otherwise the request is rejected.

#### create

{@inheritdoc}

**The required field if `externalUrl` is not specified.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### externalUrl

URL of the file that is stored externally on a third party service. Either `externalUrl` or `content` must be specified, but not both.

For a file added to an entity field, the field must be configured to store files externally, and the MIME type of the file must be allowed by the configuration of that field. For a file of an attachment, the MIME type of the file must be allowed by the attachment configuration of the attachment target entity. Otherwise the request is rejected.

#### create

{@inheritdoc}

**The required field if `content` is not specified.**

#### update

{@inheritdoc}

### parent

The entity to which this file belongs.

#### create

{@inheritdoc}

**The required field.**

**Note:**
This field is set automatically in case a file is linked to another entity in a request.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### parentFieldName

#### create

{@inheritdoc}

**The required field.**

**Note:**
This field is set automatically in case a file is linked to another entity in a request.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### owner

#### get_subresource

Retrieve a record of the user who is the owner of a specific file record.

#### get_relationship

Retrieve the ID of the user who is the owner of a specific file record.

#### update_relationship

Replace the owner of a specific file record.

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

### parent

#### get_subresource

Retrieve the entity to which a specific file belongs.

#### get_relationship

Retrieve the ID of the entity to which a specific file belongs.
