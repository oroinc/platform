<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;

class HeadType extends AbstractContainerType
{
    const NAME = 'head';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
