<?php

namespace Oro\Component\Layout\Block\Type;

abstract class AbstractContainerType extends AbstractType
{
    #[\Override]
    public function getParent()
    {
        return ContainerType::NAME;
    }
}
