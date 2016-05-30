<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Sets "exclusion_policy = all" for the entity. It means that the configuration
 * of all fields and associations was completed.
 */
class FinishCompletionOfDefinition implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll()) {
            // mark the entity configuration as processed
            $definition->setExcludeAll();
        }
    }
}
