# Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue

## ACTIONS

### get

Retrieve a specific localized fallback value record.

{@inheritdoc}

### get_list

Retrieve a collection of localized fallback value records.

{@inheritdoc}

### create

Create a new localized fallback value record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "localizedfallbackvalues",
    "attributes": {
      "fallback": null,
      "string": "Name",
      "text": null
    },
    "relationships": {
      "localization": {
        "data": {
          "type": "localizations",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific localized fallback value record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "localizedfallbackvalues",
    "id" : "1",
    "attributes": {
      "fallback": null,
      "string": "Name",
      "text": null
    },
    "relationships": {
      "localization": {
        "data": {
          "type": "localizations",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

## SUBRESOURCES

### localization

#### get_subresource

Retrieve a record of localization assigned to a specific localized fallback value record.

#### get_relationship

Retrieve ID of localization record assigned to a specific localized fallback value record.

#### update_relationship

Replace localization assigned to a specific localized fallback value record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "localizations",
    "id": "1"
  }
}
```
{@/request}
