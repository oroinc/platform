<?php

namespace Oro\Bundle\NotificationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for checking if recipient list is not empty.
 */
class RecipientListNotEmpty extends Constraint
{
    public string $message = 'oro.notification.validators.recipient_list.empty.message';
}
