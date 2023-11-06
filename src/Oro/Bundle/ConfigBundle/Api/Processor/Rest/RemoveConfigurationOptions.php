<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\Rest;

use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a configuration data request to not return configuration options.
 */
class RemoveConfigurationOptions implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $context->addConfigExtra(new FilterFieldsConfigExtra([$context->getClassName() => []]));
    }
}
