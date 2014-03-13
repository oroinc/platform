<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class SegmentFilterType extends AbstractType
{
    const NAME = 'oro_segment_segment_filter';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
