<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\SwitchFormExtension;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Switches to Data API form extension.
 */
class InitializeApiFormExtension extends SwitchFormExtension implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext|FormContext $context */

        if ($this->isApiFormExtensionActivated($context)) {
            // the API form extension is already activated
            return;
        }

        $this->switchToApiFormExtension($context);
        $this->rememberContext($context);
        $this->metadataTypeGuesser->setEntityMapper($context->getEntityMapper());
        $this->metadataTypeGuesser->setIncludedEntities($context->getIncludedEntities());
        $this->metadataTypeGuesser->setMetadataAccessor(new ContextParentMetadataAccessor($context));
        $this->metadataTypeGuesser->setConfigAccessor(new ContextParentConfigAccessor($context));
    }
}
