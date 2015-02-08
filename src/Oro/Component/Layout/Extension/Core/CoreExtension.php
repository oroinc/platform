<?php

namespace Oro\Component\Layout\Extension\Core;

use Oro\Component\Layout\Block\Type;
use Oro\Component\Layout\Extension\AbstractExtension;

class CoreExtension extends AbstractExtension
{
    protected function loadTypes()
    {
        return [
            new Type\BaseType(),
            new Type\ContainerType()
        ];
    }
}
