<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\AbstractBlockType;

class StyleBlockType extends AbstractBlockType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'container';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'style';
    }
}
