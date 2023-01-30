<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

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

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */
        if ($context->getFilters()->has($this->filterName)) {
            $isCaseInsensitive = (bool) $this->configManager->get($this->sensitivityConfigOptionName);
            $context->getFilters()->get($this->filterName)->setCaseInsensitive($isCaseInsensitive);
        }
    }
}
