<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contains the properties of the constraint definition which checks uniqueness of user by email.
 */
class UniqueUserEmail extends Constraint
{
    public $message = 'oro.user.message.user_email_exists';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_user.validator.unique_user_email';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
