# Oro\Bundle\EmailBundle\Api\Model\EmailContextItem

## ACTIONS

### get_list

Retrieve a collection of records that can be added to the context of a specific email entity
as well as records that are already added to the context.

{@request:json_api}
The `entityUrl` link in the `links` section contains a URL of an entity associated with a record.

Example:

```JSON
{
  "data": {
    "type": "emailcontext",
    "id": "users-1",
    "links": {
      "entityUrl": "http://my-site.com/admin/user/view/1"
    }
  }
}
```
{@/request}

## FIELDS

### entityName

The name of an entity associated with an email context record.

### entity

An entity associated with an email context record.

### isContext

Indicates whether a record is already added to the context of the email entity.

## FILTERS

### entities

The list of entity types to search. By default, all entities are searched.

### searchText

A string to be searched.

### messageId

The value of the 'Message-ID' header of the email message to be searched. It is required filter.

### searchText

A string to be searched. It cannot be specified together with 'from', 'to', 'cc', 'isContext' or 'excludeCurrentUser' filters.

### from

The email address of the email message author.

### to

The email address(es) of the primary recipient(s) of the email message.

### cc

The email address(es) of other recipient(s) of the email message.

### excludeCurrentUser

The filter that allows to exclude the logged-in user from the result.
