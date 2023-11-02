<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads configuration options.
 */
class LoadConfigurationOptions implements ProcessorInterface
{
    private ConfigurationRepository $configRepository;

    public function __construct(ConfigurationRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        $options = [];
        $scope = $context->get(GetScope::CONTEXT_PARAM);
        /** @var string[] $optionKeys */
        $optionKeys = $context->get(LoadConfigurationOptionKeys::OPTION_KEYS);
        foreach ($optionKeys as $optionKey) {
            $option = $this->configRepository->getOption($optionKey, $scope);
            if (null !== $option) {
                $options[] = $option;
            }
        }
        $context->setResult($options);
    }
}
