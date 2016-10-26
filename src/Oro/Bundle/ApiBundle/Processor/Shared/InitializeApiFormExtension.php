<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ContextConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\ContextMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\FormContext;

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
        /** @var Context|FormContext $context */

        if ($this->isApiFormExtensionActivated($context)) {
            // the API form extension is already activated
            return;
        }

        $this->switchToApiFormExtension($context);
        $this->rememberContext($context);
        $this->metadataTypeGuesser->setIncludedObjects($context->getIncludedObjects());
        $this->metadataTypeGuesser->setMetadataAccessor(new ContextMetadataAccessor($context));
        $this->metadataTypeGuesser->setConfigAccessor(new ContextConfigAccessor($context));
    }
}
