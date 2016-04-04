<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Updates the form with default data.
 */
class SetFormData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if (!$context->hasResult()) {
            // no data
            return;
        }
        if (!$context->hasForm()) {
            // no form
            return;
        }

        $context->getForm()->setData($context->getResult());
    }
}
