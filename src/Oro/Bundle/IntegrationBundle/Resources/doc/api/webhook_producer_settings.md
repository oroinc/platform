# Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings

## ACTIONS

### get

Retrieve a specific webhook record.

### get_list

Retrieve a collection of webhook records.

### create

Create a new webhook record.

The created record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "webhooks",
    "attributes": {
      "notificationUrl": "https://example.com/webhook",
      "enabled": true,
      "secret": "abcdef",
      "verifySsl": true
    },
    "relationships": {
      "topic": {
        "data": {
          "type": "webhooktopics",
          "id": "order.created"
        }
      },
      "format": {
        "data": {
          "type": "webhookformats",
          "id": "default"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific webhook record.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "webhooks",
    "id": "1",
    "attributes": {
      "enabled": false
    }
  }
}
```
{@/request}

### delete

Delete a specific webhook record.

### delete_list

Delete a collection of webhook records.

## FIELDS

### notificationUrl

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### topic

The topic that identifies the webhook event.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### enabled

#### create

{@inheritdoc}

**The field is optional. Default value: true**

#### update

{@inheritdoc}

### verifySsl

#### create

{@inheritdoc}

**The field is optional. Default value: true**

#### update

{@inheritdoc}

### format

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### system

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### organization

#### get_subresource

Retrieve a record of the organization a specific webhook record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific webhook record belongs to.

#### update_relationship

Replace the organization a specific webhook record belongs to.

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

### owner

#### get_subresource

Retrieve a record of the user who is an owner of a specific webhook record.

#### get_relationship

Retrieve the ID of the user who is an owner of a specific webhook record.

#### update_relationship

Replace the owner of a specific webhook record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
    "id": "1"
  }
}
```
{@/request}
