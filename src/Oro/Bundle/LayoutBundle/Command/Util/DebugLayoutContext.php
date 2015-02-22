<?php

namespace Oro\Bundle\LayoutBundle\Command\Util;

use Oro\Component\Layout\LayoutContext;

class DebugLayoutContext extends LayoutContext
{
    /**
     * {@inheritdoc}
     */
    public function getDataResolver()
    {
        if ($this->resolver === null) {
            $this->resolver = new DebugOptionsResolver();
        }

        return $this->resolver;
    }
}
