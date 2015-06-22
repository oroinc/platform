<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RelatedEntity extends Constraint
{
    public $message = 'The entity was not found.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_form.related_entity_validator';
    }
}
