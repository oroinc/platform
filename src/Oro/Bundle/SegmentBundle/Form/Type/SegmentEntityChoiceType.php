<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

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
