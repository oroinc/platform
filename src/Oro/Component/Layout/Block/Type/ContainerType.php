<?php

namespace Oro\Component\Layout\Block\Type;

// @TODO: Should be updated before close story BAP-7148
class ContainerType extends AbstractType
{
    const NAME = 'layout_container';

    /**
    * {@inheritdoc}
    */
    public function getName()
    {
        return self::NAME;
    }
}
