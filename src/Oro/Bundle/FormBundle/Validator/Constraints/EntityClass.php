<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * EntityClass constraint
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_METHOD)]
class EntityClass extends Constraint
{
    public $message = 'This value should be entity class or alias.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return 'oro_form.entity_class_validator';
    }
}
