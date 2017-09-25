<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DefinitionQueryConstraint extends Constraint
{
    /** @var string */
    public $message = 'oro.query_designer.not_accessible_class';

    /** @var string */
    public $messageColumn = 'oro.query_designer.not_accessible_class_column';

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
        return 'oro_query_designer.definition_query_validator';
    }
}
