<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Owner extends Constraint
{
    public $message = 'The given value {{ value }} cannot be set as {{ owner }} for given entity for security reason.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'owner_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
