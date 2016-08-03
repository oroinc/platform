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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_segment_entity_choice';
    }
}
