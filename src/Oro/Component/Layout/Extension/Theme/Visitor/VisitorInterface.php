<?php

namespace Oro\Component\Layout\Extension\Theme\Visitor;

use Oro\Component\Layout\ContextInterface;

interface VisitorInterface
{
    public function walkUpdates(array &$updates, ContextInterface $context);
}
