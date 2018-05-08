<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds the form using the form builder from the context.
 */
class BuildForm implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if ($context->hasForm()) {
            // the form is already built
            return;
        }
        if (!$context->hasFormBuilder()) {
            // the form cannot be built because the form builder does not exist
            return;
        }

        // build the form and add it to the context
        $context->setForm($context->getFormBuilder()->getForm());
        // remove the form builder from the context
        $context->setFormBuilder();
    }
}
