<?php

namespace Oro\Component\Layout\Block\Type;

class ContainerType extends AbstractType
{
    const NAME = 'container';

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
