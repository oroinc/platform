# Oro\Bundle\UserBundle\Entity\User

## ACTIONS  

### get

Retrieve a specific user record.

{@inheritdoc}

### get_list

Retrieve a collection of user records.

{@inheritdoc}

### create

Create a new user record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"users",
      "attributes":{  
         "username":"testapiuser",
         "email":"testuser@oroinc.com",
         "firstName":"Bob",
         "lastName":"Fedeson"
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

Edit a specific user record.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{  
   "data":{  
      "type":"users",
      "id":"54",
      "attributes":{  
         "phone":"455-78-54",
         "title":"administrator",
         "password_expires_at":"2017-01-01T00:00:00Z",
         "middleName":"Karl",
         "birthday":"1964-07-05",
         "enabled":true
      },
      "relationships":{  
         "businessUnits":{  
            "data":[  
               {  
                  "type":"businessunits",
                  "id":"1"
               }
            ]
         },
         "roles":{  
            "data":[  
               {  
                  "type":"userroles",
                  "id":"3"
               }
            ]
         },
         "organizations":{  
            "data":[  
               {  
                  "type":"organizations",
                  "id":"1"
               },
               {  
                  "type":"organizations",
                  "id":"2"
               }
            ]
         },
         "auth_status":{  
            "data":{  
               "type":"authstatuses",
               "id":"active"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific user record.

{@inheritdoc}

### delete_list

Delete a collection of user records.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### emails

An array of email addresses.

The **email** property is a string contains an email address.

Example of data: **\[{"email": "first@email.com"}, {"email": "second@email.com"}\]**

#### create, update

An array of email addresses.

The **email** property is a string contains an email address.

Example of data: **\[{"email": "first@email.com"}, {"email": "second@email.com"}\]**

**Please note:**

*Data should contain all of email addresses of the user.*

### username

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### email

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### firstName

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### lastName

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

## SUBRESOURCES

### avatar

#### get_subresource

Retrieve the avatar configured for a specific user record.

#### get_relationship

Retrieve the ID of the avatar configured for a specific user.

#### update_relationship

Replace the avatar for a specific user.

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

### businessUnits

#### get_subresource

Retrieve the business unit records that a specific user has access to.

#### get_relationship

Retrieve the IDs of the business units that a specific user has access to.

#### add_relationship

Set the business units that a specific user has access to.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "businessunits",
      "id": "1"
    },
    {
      "type": "businessunits",
      "id": "2"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the business units that a specific user has access to.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "businessunits",
      "id": "1"
    },
    {
      "type": "businessunits",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the business units that a specific user has access to.

### groups

#### get_subresource

Retrieve the group records that a specific user belongs to.

#### get_relationship

Retrieve the IDs of the groups that a specific user belongs to.

#### add_relationship

Set the groups that a specific user belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "usergroups",
      "id": "1"
    },
    {
      "type": "usergroups",
      "id": "2"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the groups that a specific user belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "usergroups",
      "id": "1"
    },
    {
      "type": "usergroups",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the groups that a specific user belongs to.

### organization

#### get_subresource

Retrieve the record of the organization that a specific user belongs to.

#### get_relationship

Retrieve the ID of the organization that a specific user belongs to.

#### update_relationship

Replace the organization that a specific user belongs to.

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

### organizations

#### get_subresource

Retrieve the records of the organizations that a specific user has access to.

#### get_relationship

Retrieve the IDs of the organizations that a specific user has access to.

#### add_relationship

Set the organizations that a specific user has access to.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "organizations",
      "id": "1"
    },
    {
      "type": "organizations",
      "id": "2"
    },
    {
      "type": "organizations",
      "id": "5"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the organizations that a specific user has access to.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "organizations",
      "id": "1"
    },
    {
      "type": "organizations",
      "id": "2"
    },
    {
      "type": "organizations",
      "id": "5"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the organizations that a specific user has access to.

### owner

#### get_subresource

Retrieve the record of the business unit that is the owner of a specific user record.

#### get_relationship

Retrieve the ID of the business unit that is the owner of a specific user record.

#### update_relationship

Replace the owner of a specific user record.

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

Retrieve the records of the roles that are assigned to a specific user.

#### get_relationship

Retrieve the IDs of the roles that are assigned to a specific user.

#### add_relationship

Set the roles that will be assigned to a specific user.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "userroles",
      "id": "3"
    }
  ]
}
```
{@/request}

#### update_relationship

Replace the roles for a specific user.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "userroles",
      "id": "3"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove the roles that are assigned to a specific user.

### auth_status

#### get_subresource

Retrieve the user's authentication status.

#### get_relationship

Retrieve the ID of the user's authentication status.

#### update_relationship

Replace the user's authentication status.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "authstatuses",
    "id": "active"
  }
}
```
{@/request}


# Extend\Entity\EV_Auth_Status

## ACTIONS

### get

Retrieve a specific authentication status record.

The authentication status defines the actuality of the user's password, whether it is active, expired, or locked.

### get_list

Retrieve a collection of authentication status records.

The authentication status defines the actuality of the user's password, whether it is active, expired, or locked.
