<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\Rest;

use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a configuration data request to return all fields of configuration options.
 */
class ExpandConfigurationOptions implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $context->addConfigExtra(new ExpandRelatedEntitiesConfigExtra(['options']));
    }
}
