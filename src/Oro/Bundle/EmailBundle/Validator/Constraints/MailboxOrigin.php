<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that a mailbox has at least one folder for sent emails.
 */
class MailboxOrigin extends Constraint
{
    public string $message = 'At least one folder for sent emails is required. '
        . 'Make sure you clicked button "%button%" and there are some folders for incoming messages.';
}
