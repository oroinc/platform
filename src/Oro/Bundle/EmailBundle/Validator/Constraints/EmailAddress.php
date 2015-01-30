<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EmailAddress extends Constraint
{
    public $message = 'This value contains not valid email address.';
    public $checkMX = false;
    public $checkHost = false;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_email.email_address_validator';
    }
}
