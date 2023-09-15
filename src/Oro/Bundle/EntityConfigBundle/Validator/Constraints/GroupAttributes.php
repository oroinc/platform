<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class GroupAttributes extends Constraint
{
    /**
     * @var string
     */
    public $duplicateAttributesMessage = 'oro.entity_config.validator.attribute_family.duplicate_attributes';

    /**
     * @var string
     */
    public $missingSystemAttributesMessage = 'oro.entity_config.validator.attribute_family.missing_system_attributes';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return GroupAttributesValidator::ALIAS;
    }
}
