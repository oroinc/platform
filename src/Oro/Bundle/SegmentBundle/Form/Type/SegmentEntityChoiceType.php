<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

class SegmentEntityChoiceType extends EntityChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_segment_entity_choice';
    }
}
