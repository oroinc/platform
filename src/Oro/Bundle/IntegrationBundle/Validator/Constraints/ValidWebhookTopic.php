<?php

namespace Oro\Bundle\IntegrationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the topic of a WebhookProducerSettings entity is one of the available webhook topics.
 */
class ValidWebhookTopic extends Constraint
{
    public string $message = 'oro.integration.validator.webhook_topic.invalid';
}
