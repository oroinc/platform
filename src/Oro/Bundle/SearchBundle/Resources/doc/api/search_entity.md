# Oro\Bundle\SearchBundle\Api\Model\SearchEntity

## ACTIONS  

### get

Retrieve a specific search entity record.

### get_list

Retrieve a collection of search entity records.

## FIELDS

### entityType

The type of an API resource object that represents an entity.

### entityName

The localized name of an entity.

### searchable

Indicates whether a view permission is granted to an entity for the current logged-in user, so it can be used in the search.

### fields

An array of fields that can be used during a search for an entity.

Each element of the array is an object with the following properties:

**name** is a string that contains the name of the field.

**type** is a string that contains the type of the field. The field type can be `integer`, `decimal`, `datetime` or `text`.

**entityFields** is an array of strings that contains field names or paths from which data are retrieved. E.g.: **\["email", "emails.email"\]**

Example of data: **\[{"name": "code", "type": "text", "entityFields": \["code"\]}, {"name": "allText", "type": "text", "entityFields": \["code", "name"\]}\]**
