<?php

namespace Oro\Bundle\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueRole extends Constraint
{
    public $message = 'Role {{ role }} already exists.';
    
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
    
    public function validatedBy()
    {
        return 'oro_user.unique_role';
    }
}
