<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * When applied to an config item of ConfigType form, this constraint allows
 * to checks whether the value is not lower then the original one.
 */
class NotLessThanOriginalValue extends Constraint
{
    /** @var string */
    public $message = 'oro.entity_extend.validator.not_less_than_original';

    /** @var string */
    public $scope;

    /** @var string */
    public $option;

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return static::PROPERTY_CONSTRAINT;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredOptions()
    {
        return ['scope', 'option'];
    }
}
