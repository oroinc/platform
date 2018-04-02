<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Switches to default form extension.
 * As before the forms were switched in Data API mode (see the InitializeApiFormExtension processor)
 * and an action called this processor can work in different contexts, we should returns the forms
 * to the original state to prevent possible collisions.
 */
class RestoreDefaultFormExtension extends SwitchFormExtension implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if (!$this->isApiFormExtensionActivated($context)) {
            // the default form extension is already restored
            return;
        }

        $this->switchToDefaultFormExtension($context);
        $this->restoreContext($context);
    }
}
