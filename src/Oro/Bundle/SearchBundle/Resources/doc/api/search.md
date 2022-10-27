# Oro\Bundle\SearchBundle\Api\Model\SearchItem

## ACTIONS

### get_list

Retrieve a collection of search records.

{@request:json_api}
The `entityUrl` link in the `links` section contains a URL of an entity associated with a search record. 

Example:

```JSON
{
  "data": {
    "type": "search",
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

The name of an entity associated with a search record.

### entity

An entity associated with a search record.

## FILTERS

### searchText

A string to be searched.

### entities

The list of entity types to search. By default, all entities are searched.
