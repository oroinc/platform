<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sorts loaded configuration options in the same order as configuration option keys
 * by which these options were loaded.
 */
class SortLoadedConfigurationOptions implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        /** @var string[] $optionKeys */
        $optionKeys = $context->get(LoadConfigurationOptionKeys::OPTION_KEYS);
        $optionMap = [];
        /** @var ConfigurationOption[] $options */
        $options = $context->getResult();
        foreach ($options as $option) {
            $optionMap[$option->getKey()] = $option;
        }
        $sortedOptions = [];
        foreach ($optionKeys as $optionKey) {
            if (isset($optionMap[$optionKey])) {
                $sortedOptions[] = $optionMap[$optionKey];
            }
        }
        $context->setResult($sortedOptions);
    }
}
