<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email;

/**
 * This constraint is used to check that a string represents a valid email address.
 */
class EmailAddress extends Email
{
    public $message = 'This value contains not valid email address.';
}
