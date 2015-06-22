<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class EntityClass extends Constraint
{
    public $message = 'This value should be entity class or alias.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_form.entity_class_validator';
    }
}
