# Oro\Bundle\TranslationBundle\Entity\TranslationKey

## ACTIONS

### get

Retrieve a specific visual element in the application, like a label, an information massage,
a notification, an alert, a workflow status, etc.

### get_list

Retrieve a collection of visual elements in the application, like labels, information massages,
notifications, alerts, workflow statuses, etc.

### create

Update a translated value for a specific visual element in the application, like a label, an information massage,
a notification, an alert, a workflow status, etc.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "translations",
    "attributes": {
      "domain": "messages",
      "key": "test.key",
      "languageCode": "en_US",
      "translatedValue": "New translated value"
    }
  }
}
```
{@/request}

To remove a translated value specify `null` for the **translatedValue** field.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "translations",
    "attributes": {
      "domain": "messages",
      "key": "test.key",
      "languageCode": "en_US",
      "translatedValue": null
    }
  }
}
```
{@/request}

### update

Update a translated value for a specific visual element in the application, like a label, an information massage,
a notification, an alert, a workflow status, etc.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "translations",
    "id": "1-en_US",
    "attributes": {
      "translatedValue": "New translated value"
    }
  }
}
```
{@/request}

To remove a translated value specify `null` for the **translatedValue** field.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "translations",
    "id": "1-en_US",
    "attributes": {
      "translatedValue": null
    }
  }
}
```
{@/request}

## FIELDS

### domain

A string that represents a logical affiliation of text items to a particular functionality  
(e.g. messages, jsmessages, validators, etc.).

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### key

A string that identifies the text item and is used to find its translation to the target language
(e.g. oro.ui.updated_at) in the application.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### languageCode

The code of the language of the text items available to the user.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### hasTranslation

Indicates whether the text item are translated to the target language

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### value

The translation of the text item to the target language or a to fallback language
if there is no translation for the target language.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### englishValue

The english translation of the text item.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### translatedValue

The translation of the text item to the target language.

#### create, update

{@inheritdoc}

**The required field.**
