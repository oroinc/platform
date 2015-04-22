<?php

namespace Oro\Component\Layout\Block\Type;

abstract class AbstractContainerType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ContainerType::NAME;
    }
}
