<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;

/**
 * Builds the form using the form builder from the Context.
 */
class BuildForm implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $form = $context->getForm();
        if (null !== $form) {
            // a form is already built
            return;
        }
        $formBuilder = $context->getFormBuilder();
        if (null === $formBuilder) {
            // a form cannot be built because a form builder does not exist
            return;
        }

        // build the form and add it to the Context
        $context->setForm($formBuilder->getForm());
        // remove the form builder from the Context
        $context->setFormBuilder();
    }
}
