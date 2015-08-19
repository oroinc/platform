<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Oro\Component\Layout\LayoutContext;

class DebugLayoutContext extends LayoutContext
{
    /**
     * {@inheritdoc}
     */
    protected function createResolver()
    {
        return new DebugOptionsResolver();
    }
}
