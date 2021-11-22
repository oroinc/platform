<?php

namespace Oro\Bundle\LoggerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Check the list of email log recipients specified in a string and separated by a semicolon.
 */
class EmailRecipientsList extends Constraint
{
    public string $message = 'oro.logger.system_configuration.fields.email_notification_recipients.error';
}
