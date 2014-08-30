<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueEnumName extends Constraint
{
    public $message = "An enum with this name already exist.";

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
