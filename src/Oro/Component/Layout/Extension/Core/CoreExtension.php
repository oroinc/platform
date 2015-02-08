<?php

namespace Oro\Component\Layout\Extension\Core;

use Oro\Component\Layout\AbstractExtension;
use Oro\Component\Layout\Block\Type;

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
