<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Oro\Component\Layout\ContextInterface;

/**
 * Interface for the layout name providers used in the layout data collector.
 */
interface DataCollectorLayoutNameProviderInterface
{
    public function getNameByContext(ContextInterface $context): string;
}
