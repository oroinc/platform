# Oro\Bundle\IntegrationBundle\Api\Model\WebhookTopic

## ACTIONS

### get

Retrieve a specific webhook topic record.

### get_list

Retrieve a collection of available webhook topic records.

This resource provides a list of all available webhook topics that can be used when configuring webhooks.
The list is dynamically generated based on the registered webhook configurations in the system.
Each topic includes a machine-readable identifier (``id``).

## FIELDS

### id

The unique identifier of the webhook topic.

### label

The human-readable label of the webhook topic.
