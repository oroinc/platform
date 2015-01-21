<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block;

use Oro\Component\Layout\ContainerBlockType;

class RootType extends ContainerBlockType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'root';
    }
}
