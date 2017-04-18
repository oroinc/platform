<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Oro\Bundle\UserBundle\Validator\UserWithoutRoleValidator;

class UserWithoutRole extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.user.message.user_without_role';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UserWithoutRoleValidator::ALIAS;
    }
}
