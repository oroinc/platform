<?php

namespace Oro\Bundle\EntityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint designed to be used on a parent entity that have a relations with EntityFieldFallbackValue`s
 */
class RelatedEntityFieldFallbackValueConstraint extends Constraint
{
    /**
     * @var Constraint[]
     */
    public $scalarValueConstraints = [];

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_entity.related_entity_field_fallback_value_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'scalarValueConstraints';
    }
}
