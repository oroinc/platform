<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether all specified users have at least one role.
 */
class UserWithoutRole extends Constraint
{
    public string $message = 'oro.user.message.user_without_role';
}
