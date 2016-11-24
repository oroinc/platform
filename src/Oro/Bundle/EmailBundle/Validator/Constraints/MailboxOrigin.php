<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MailboxOrigin extends Constraint
{
    /** @var string */
    public $message = 'At least one folder for sent emails is required. '
        . 'Make sure you clicked button "%button%" and there are some folders for incoming messages.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_email.validator.mailbox_origin';
    }
}
