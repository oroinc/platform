# Oro\Bundle\UserBundle\Entity\Group

## ACTIONS  

### get

Retrieve a specific user group record.

{@inheritdoc}

### get_list

Retrieve a collection of user group records.

{@inheritdoc}

### create

Create a new user group record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"usergroups",
      "attributes":{  
         "name":"HQ Administrators"
      },
      "relationships":{  
         "owner":{  
            "data":{  
               "type":"businessunits",
               "id":"1"
            }
         },
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

Edit a specific user group record.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"usergroups",
      "id":"10",
      "attributes":{  
         "name":"HQ Administrators"
      },
      "relationships":{  
         "owner":{  
            "data":{  
               "type":"businessunits",
               "id":"1"
            }
         },
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

Delete a specific user group record.

{@inheritdoc}

### delete_list

Delete a collection of user group records.

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

Retrieve the record of the organization a specific user group record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific user group record belongs to.

#### update_relationship

Replace the organization a specific user group belongs to.

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

Retrieve the record of the business unit which is the owner of a specific user group record.

#### get_relationship

Retrieve the ID of the business unit which is the owner of a specific user group record.

#### update_relationship

Replace the owner of a specific user group record.

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

### roles

#### get_subresource

Retrieve the records of the roles that are assigned to the user group. **Currently is not supported and will be removed.** 

#### get_relationship

Retrieve the IDs of the roles that are assigned to the user group. **Currently is not supported and will be removed.** 

#### add_relationship

Set the roles that will be assigned to the user group. **Currently is not supported and will be removed.** 

#### update_relationship

Replace the roles that are assigned to the user group. **Currently is not supported and will be removed.** 

#### delete_relationship

Remove the roles that are assigned to the user group. **Currently is not supported and will be removed.** 
