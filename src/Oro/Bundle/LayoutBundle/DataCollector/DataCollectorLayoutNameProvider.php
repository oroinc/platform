<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Oro\Component\Layout\ContextInterface;

/**
 * Provides the layout name for data collector by delegating execution to inner providers.
 */
class DataCollectorLayoutNameProvider implements DataCollectorLayoutNameProviderInterface
{
    /** @var iterable<DataCollectorLayoutNameProviderInterface> */
    private iterable $layoutNameProviders;

    public function __construct(iterable $layoutNameProviders)
    {
        $this->layoutNameProviders = $layoutNameProviders;
    }

    public function getNameByContext(ContextInterface $context): string
    {
        foreach ($this->layoutNameProviders as $layoutNameProvider) {
            $name = $layoutNameProvider->getNameByContext($context);
            if ($name) {
                return $name;
            }
        }

        return '';
    }
}
