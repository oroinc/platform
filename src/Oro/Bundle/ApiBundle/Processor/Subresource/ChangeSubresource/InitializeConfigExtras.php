<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Config\Extra\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets an initial list of requests for configuration data.
 * It is supposed that the list was initialized if
 * the EntityDefinitionConfigExtra is already exist in the context.
 */
class InitializeConfigExtras implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeSubresourceContext $context */

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
        $context->addConfigExtra(new CustomizeLoadedDataConfigExtra());
        $context->addConfigExtra(new DataTransformersConfigExtra());
    }
}
