<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UsedPassword extends Constraint
{
    /** @var string */
    public $message = 'oro.user.message.password_already_used';

    /** @var int */
    public $userId;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'userId';
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_used_password';
    }
}
