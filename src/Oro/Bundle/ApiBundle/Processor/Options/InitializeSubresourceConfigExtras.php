<?php

namespace Oro\Bundle\ApiBundle\Processor\Options;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets an initial list of requests for configuration data.
 * It is supposed that the list was initialized if
 * the EntityDefinitionConfigExtra is already exist in the context.
 */
class InitializeSubresourceConfigExtras implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var OptionsContext $context */

        if ($context->hasConfigExtra(EntityDefinitionConfigExtra::NAME)) {
            // config extras are already initialized
            return;
        }

        $context->addConfigExtra(
            new EntityDefinitionConfigExtra(
                $context->getAction(),
                $context->isCollection(),
                $context->getParentClassName(),
                $context->getAssociationName()
            )
        );
        $context->addConfigExtra(new FilterIdentifierFieldsConfigExtra());
    }
}
