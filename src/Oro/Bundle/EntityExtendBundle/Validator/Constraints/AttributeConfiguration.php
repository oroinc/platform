<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint class for AttributeConfigurationValidator.
 */
class AttributeConfiguration extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.entity_extend.validator.attribute_configuration.error_configuration';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return AttributeConfigurationValidator::ALIAS;
    }
}
