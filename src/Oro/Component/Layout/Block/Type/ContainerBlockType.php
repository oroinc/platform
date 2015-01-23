<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\AbstractBlockType;

// @TODO: Should be updated before close story BAP-7148
class ContainerBlockType extends AbstractBlockType
{
    /**
    * {@inheritdoc}
    */
    public function getName()
    {
        return 'container';
    }
}
