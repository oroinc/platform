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

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_entity.related_entity_field_fallback_value_validator';
    }

    #[\Override]
    public function getDefaultOption(): ?string
    {
        return 'scalarValueConstraints';
    }
}
