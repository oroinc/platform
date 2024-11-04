<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;

class RootType extends AbstractContainerType
{
    #[\Override]
    public function getName()
    {
        return 'root';
    }
}
