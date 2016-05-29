<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AccessGranted extends Constraint
{
    public $message = 'You have no access to set this value.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_api.validator.access_granted';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
