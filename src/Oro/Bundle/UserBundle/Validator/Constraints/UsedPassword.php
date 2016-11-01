<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UsedPassword extends Constraint
{
    /** @var string */
    public $message = 'oro.user.message.password_already_used';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_used_password';
    }
}
