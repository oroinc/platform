<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueEnumName extends Constraint
{
    public $message = "The enum name should be unique.";

    /** @var string */
    public $entityClassName;

    /** @var string */
    public $fieldName;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UniqueEnumNameValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return ['entityClassName', 'fieldName'];
    }
}
