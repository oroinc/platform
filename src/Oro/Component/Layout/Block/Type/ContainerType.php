<?php

namespace Oro\Component\Layout\Block\Type;

class ContainerType extends AbstractType
{
    public const NAME = 'container';

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
