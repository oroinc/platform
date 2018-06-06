<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Form\FormValidationHandler;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Performs the validation of the request data via the form from the context.
 */
class ValidateForm implements ProcessorInterface
{
    /** @var FormValidationHandler */
    private $validator;

    /**
     * @param FormValidationHandler $validator
     */
    public function __construct(FormValidationHandler $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if ($context->isFormValidationSkipped()) {
            // the form validation was not requested for this action
            return;
        }

        $form = $context->getForm();
        if (null === $form || !$form->isSubmitted()) {
            // the form does not exist or not submitted yet
            return;
        }

        $this->validator->validate($form);
    }
}
