<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block;

use Oro\Component\Layout\ContainerBlockType;

class HeaderType extends ContainerBlockType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'header';
    }
}
