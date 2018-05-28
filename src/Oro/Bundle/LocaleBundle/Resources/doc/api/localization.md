# Oro\Bundle\LocaleBundle\Entity\Localization

## ACTIONS

### get

Retrieve a specific localization record.

{@inheritdoc}

### get_list

Retrieve a collection of localization records.

{@inheritdoc}

## SUBRESOURCES

### childLocalizations

#### get_subresource

Retrieve a record of child localization assigned to a specific localization record.

#### get_relationship

Retrieve the ID of child localization record assigned to a specific localization record.

### parentLocalization

#### get_subresource

Retrieve a record of parent localization assigned to a specific localization record.

#### get_relationship

Retrieve the ID of parent localization record assigned to a specific localization record.

### titles

#### get_subresource

Retrieve the service records that store the localization titles localized data.

#### get_relationship

Retrieve the ID's of service records that store the localization titles localized data.

# Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue

## ACTIONS

### get

Retrieve a specific LocalizedFallbackValue record.

{@inheritdoc}

### get_list

Retrieve a collection of LocalizedFallbackValue records.

{@inheritdoc}

### create

Create a new LocalizedFallbackValue record.

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
        "type":"localizations",
        "id":"1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific LocalizedFallbackValue record.

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
        "type":"localizations",
        "id":"1"
        }
      }
    }
  }
}
```
{@/request}

## FIELDS

## SUBRESOURCES

### localization

#### get_subresource

Retrieve a record of localization assigned to a specific localizedfallbackvalues record.

#### get_relationship

Retrieve ID of localization record assigned to a specific localizedfallbackvalues record.

#### update_relationship

Replace localization assigned to a specific localizedfallbackvalues record

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
