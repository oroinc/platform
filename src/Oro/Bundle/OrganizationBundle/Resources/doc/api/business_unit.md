# Oro\Bundle\OrganizationBundle\Entity\BusinessUnit

## ACTIONS  

### get

Retrieve a specific business unit record.

{@inheritdoc}

### get_list

Retrieve a collection of business unit records.

{@inheritdoc}

### create
    
Create a new business unit record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"businessunits",
      "attributes":{  
         "name":"Acme, Central",
         "phone":"798-682-5917",
         "email":"central@acme.inc",
         "fax":"547-58-95"
      },
      "relationships":{  
         "organization":{  
            "data":{  
               "type":"organizations",
               "id":"1"
            }
         },
         "users":{  
            "data":[  
               {  
                  "type":"users",
                  "id":"1"
               },
               {  
                  "type":"users",
                  "id":"2"
               }
            ]
         },
         "owner":{  
            "data":{  
               "type":"businessunits",
               "id":"1"
            }
         }
      }
   }
}
```
{@/request}

### update

Edit a specific business unit record.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"businessunits",
      "id":"4",
      "attributes":{  
         "extend_description":"Business units represent a group of users with similar business or administrative tasks/roles.",
         "phone":"798-682-59-17",
         "website":"www.www.vom"
      },
      "relationships":{  
         "users":{  
            "data":[  
               {  
                  "type":"users",
                  "id":"1"
               },
               {  
                  "type":"users",
                  "id":"2"
               }
            ]
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific business unit record

{@inheritdoc}

### delete_list

Delete a collection of business unit records.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### name

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

## SUBRESOURCES

### organization

#### get_subresource

Retrieve the records of the organization which a specific business unit record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific business unit belongs to.

#### update_relationship

Replace the organization record a specific business unit record belongs to.

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

Retrieve the records of the business unit which is the owner of a specific business unit record.

#### get_relationship

Retrieve the ID of the business unit record which is the owner of a specific business unit record.

#### update_relationship

Replace the owner of a specific business unit record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "businessunits",
    "id": "1"
  }
}
```
{@/request}

### users

#### get_subresource

Retrieve the records of users who have access to a specific business unit record.

#### get_relationship

Retrieve the IDs of the users who have access to a specific business unit record.

#### add_relationship

Set the user records that will have access to a specific business unit record.

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

Replace the user records that have access to a specific business unit record.

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

Remove user records from a specific business unit record.
