# Oro\Bundle\UserBundle\Entity\Role

## ACTIONS  

### get

Retrieve a specific user role record.

{@inheritdoc}

### get_list

Retrieve a collection of user role records.

{@inheritdoc}

### create

Create a new user role.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"userroles",
      "attributes":{  
         "extend_description":"A guest role",
         "role":"IS_AUTHENTICATED_AT_FIRST",
         "label":"Guest"
      },
      "relationships":{  
         "organization":{  
            "data":{  
               "type":"organizations",
               "id":"1"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific user role record.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"userroles",
      "id":"10",
      "attributes":{  
         "extend_description":"A guest role new",
         "role":"IS_AUTHENTICATED_AT_FIRST",
         "label":"Guest"
      },
      "relationships":{  
         "organization":{  
            "data":{  
               "type":"organizations",
               "id":"1"
            }
         }
      }
   }
}
```
{@/request}

### delete

Remove a specific user role.

{@inheritdoc}

### delete_list

Delete a collection of user roles.

{@inheritdoc}

## FIELDS

### label

#### create

{@inheritdoc}

**The required field**

### id

#### update

{@inheritdoc}

**The required field**

## SUBRESOURCES

### organization

#### get_subresource

Retrieve the record of the organization that a specific user role belongs to.

#### get_relationship

Retrieve the ID of the organization that a specific user role belongs to.

#### update_relationship

Replace the organization that a specific user role belongs to.

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

### users

#### get_subresource

Retrieve the records of users who are assigned to a specific user role record.

#### get_relationship

Retrieve the IDs of the users who are assigned to a specific user role record.

#### add_relationship

Assign the user records to a specific user role record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    },
    {
      "type": "users",
      "id": "2"
    },
    {
      "type": "users",
      "id": "3"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the user records that are assigned to a specific user role record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    },
    {
      "type": "users",
      "id": "2"
    },
    {
      "type": "users",
      "id": "3"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove user records from a specific user role record.
