<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

/**
 * Form type for selecting entities in segment configuration.
 *
 * This form type extends the base {@see EntityChoiceType} to provide a specialized choice field
 * for selecting entities that segments can be created for. It is used in segment creation
 * and editing forms to allow administrators to specify which entity type a segment should
 * operate on. The type provides a consistent block prefix for form rendering and template
 * integration specific to segment entity selection.
 */
class SegmentEntityChoiceType extends EntityChoiceType
{
    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_segment_entity_choice';
    }
}
