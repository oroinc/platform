<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Oro\Bundle\UserBundle\Validator\UserWithoutRoleValidator;
use Symfony\Component\Validator\Constraint;

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
        return UserWithoutRoleValidator::class;
    }
}
