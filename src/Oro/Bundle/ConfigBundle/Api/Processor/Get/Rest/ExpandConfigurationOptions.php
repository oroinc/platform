<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\Get\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Adds a configuration data request to return all fields of configuration options.
 */
class ExpandConfigurationOptions implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $context->addConfigExtra(new ExpandRelatedEntitiesConfigExtra(['options']));
    }
}
