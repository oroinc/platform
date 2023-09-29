# Oro\Bundle\EmailBundle\Api\Model\EmailContextSearchItem

## ACTIONS

### get_list

Retrieve a collection of records that can be added to the context of any email entity.

{@request:json_api}
The `entityUrl` link in the `links` section contains a URL of an entity associated with a record.

Example:

```JSON
{
  "data": {
    "type": "emailcontextsearch",
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

The name of an entity associated with an email context search record.

### entity

An entity associated with an email context search record.

## FILTERS

### entities

The list of entity types to search. By default, all entities are searched.

### searchText

A string to be searched.
