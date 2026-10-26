# Oro\Bundle\ApiBundle\Model\EnumOption

## ACTIONS

### get

Retrieve a specific enum option.

### get_list

Retrieve a collection of enum options.

Use the `enumCode` filter to select options of a specific enum.
For example: `GET /api/enumoptions?filter[enumCode]=product_color&sort=priority`.

### create

Create a new option for an existing custom enum. Options cannot be added to system enums.
The resource ID must have the `enum_code.option_id` format.
New translations can be included in the same request. The translation key and domain are taken from the option ID.
The stored option name is initialized from the included English translation, the first included translation,
or `optionId` if no translations are included.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "enumoptions",
    "id": "product_color.red",
    "attributes": {
      "priority": 10,
      "default": false
    },
    "relationships": {
      "names": {
        "data": [
          {"type": "translations", "id": "red-fr"}
        ]
      }
    }
  },
  "included": [
    {
      "type": "translations",
      "id": "red-fr",
      "attributes": {
        "languageCode": "fr_FR",
        "translatedValue": "Rouge"
      }
    }
  ]
}
```
{@/request}

### update

Edit a specific enum option.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "enumoptions",
    "id": "product_color.red",
    "attributes": {
      "priority": 20
    },
    "relationships": {
      "names": {
        "data": [
          {"type": "translations", "id": "123-en_US"}
        ]
      }
    }
  },
  "included": [
    {
      "type": "translations",
      "id": "123-en_US",
      "meta": { "update": true },
      "attributes": {
        "translatedValue": "Red"
      }
    }
  ]
}
```
{@/request}

### delete

Delete a specific enum option. Options of system enums cannot be deleted.

## FIELDS

### id

The option identifier in the `enum_code.option_id` format.

#### update

{@inheritdoc}

**The required field.**

### enumCode

The enum code derived from the resource ID.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### optionId

The option identifier within the enum, derived from the resource ID.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### priority

The option sort priority.

### default

Indicates whether the option is selected by default.
Setting it to `true` clears the default flag on all other options with the same `enumCode`.

### names

A relationship to the option's `translations` resources, one per installed language.


# Oro\Bundle\EntityExtendBundle\Entity\EnumOption

## FIELDS

### name

The human readable name of the option.

### default

Determines if this option is selected by default for new records.

### priority

The order in which options are ranked.
