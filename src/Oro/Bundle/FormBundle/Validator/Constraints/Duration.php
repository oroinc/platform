<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Duration extends Constraint
{
    public $message = 'Duration does not match #:#:# or #h #m #s encoding';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_form.duration_validator';
    }
}
