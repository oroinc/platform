<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\SwitchFormExtension;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentMetadataAccessor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Switches to API form extension.
 */
class InitializeApiFormExtension extends SwitchFormExtension implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

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
