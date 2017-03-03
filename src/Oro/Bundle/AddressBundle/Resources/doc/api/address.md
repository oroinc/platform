# Oro\Bundle\AddressBundle\Entity\Address

## ACTIONS  

### get

Retrieve a specific address record.

{@inheritdoc}

### get_list

Retrieve a collection of address records.

{@inheritdoc}

### create

Create a new address record.
The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

`</api/addresses>`

```JSON
{  
   "data":{  
      "type":"addresses",
      "attributes":{  
         "label": "Home",
         "street":"1475 Harigun Drive",
         "city":"Dallas",
         "postalCode":"04759",
         "organization":"Dallas Nugets",
         "namePrefix":"Mr.",
         "firstName":"Jerry",
         "middleName":"August",
         "lastName":"Coleman",
         "nameSuffix":"d'"
      },
      "relationships":{  
         "country":{  
            "data":{  
               "type":"countries",
               "id":"US"
            }
         },
         "region":{  
            "data":{  
               "type":"regions",
               "id":"US-NY"
            }
         }
      }
   }
}
```
{@/request}


### update

Edit a specific address record.

{@inheritdoc}

{@request:json_api}
Example:

`</api/addresses/51>`

```JSON
{  
   "data":{  
      "type":"addresses",
      "id":"51",
      "attributes":{  
         "label":"Home",
         "street":"1475 Harigun Drive",
         "city":"Dallas",
         "postalCode":"04759",
         "organization":"Dallas Nugets",
         "namePrefix":"Mr.",
         "firstName":"Jerry",
         "middleName":"August",
         "lastName":"Coleman",
         "nameSuffix":"d'"
      },
      "relationships":{  
         "country":{  
            "data":{  
               "type":"countries",
               "id":"US"
            }
         },
         "region":{  
            "data":{  
               "type":"regions",
               "id":"US-NY"
            }
         }
      }
   }
}
```
{@/request}

### delete

Delete a specific address record.

{@inheritdoc}

### delete_list

Delete a collection of address records.
The list of records that will be deleted, could be limited by filters.

{@inheritdoc}

## FIELDS

### id

#### update

{@inheritdoc}

**The required field**

### street

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### city

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### postalCode

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

## SUBRESOURCES

### country

#### get_subresource

Retrieve the country record configured for a specific address record.

#### get_relationship

Retrieve the ID of the country record configured for a specific address record.

#### update_relationship

Replace the country record configured for a specific address record.

{@request:json_api}
Example:

`</api/addresses/1/relationships/country>`

```JSON
{
  "data": {
    "type": "countries",
    "id": "US"
  }
}
```
{@/request}

### region

#### get_subresource

Retrieve the region record configured for a specific address record.

#### get_relationship

Retrieve the ID of the region configured for a specific address record.

#### update_relationship

Replace the region record configured for a specific address record.

{@request:json_api}
Example:

`</api/addresses/1/relationships/region>`

```JSON
{
  "data": {
    "type": "regions",
    "id": "US-NY"
  }
}
```
{@/request}
