<?php

namespace Oro\Bundle\IntegrationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the format of a WebhookProducerSettings entity is one of the available formats.
 */
class ValidWebhookFormat extends Constraint
{
    public string $message = 'oro.integration.validator.webhook_format.invalid';
}
