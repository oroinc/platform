<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class MailboxOrigin extends Constraint
{
    /** @var string */
    public $message = 'oro.email.message.mailbox_origin_constraint';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_email.validator.mailbox_origin';
    }
}
