<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email;

class EmailAddress extends Email
{
    public $message = 'This value contains not valid email address.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_email.email_address_validator';
    }
}
