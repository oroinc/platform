<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that the current logged in user
 * is granted to change the owner for an entity.
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
