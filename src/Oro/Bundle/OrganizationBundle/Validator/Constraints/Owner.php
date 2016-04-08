<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Owner extends Constraint
{
    public $message = 'You have no access to set this value as {{ owner }}.';

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
