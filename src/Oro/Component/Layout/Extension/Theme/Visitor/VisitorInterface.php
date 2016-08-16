<?php

namespace Oro\Component\Layout\Extension\Theme\Visitor;

use Oro\Component\Layout\ContextInterface;

interface VisitorInterface
{
    /**
     * @param array $updates
     * @param ContextInterface $context
     *
     * @return array
     */
    public function walkUpdates(array $updates, ContextInterface $context);
}
