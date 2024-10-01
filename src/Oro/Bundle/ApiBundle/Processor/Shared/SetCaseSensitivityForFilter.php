<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Synchronizes filter case-insensitive comparison behavior with particular config value.
 */
class SetCaseSensitivityForFilter implements ProcessorInterface
{
    private ConfigManager $configManager;
    private string $filterName;
    private string $sensitivityConfigOptionName;

    public function __construct(ConfigManager $configManager, string $filterName, string $sensitivityConfigOptionName)
    {
        $this->configManager = $configManager;
        $this->filterName = $filterName;
        $this->sensitivityConfigOptionName = $sensitivityConfigOptionName;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $filter = $context->getFilters()->get($this->filterName);
        if ($filter instanceof ComparisonFilter) {
            $filter->setCaseInsensitive((bool)$this->configManager->get($this->sensitivityConfigOptionName));
        }
    }
}
