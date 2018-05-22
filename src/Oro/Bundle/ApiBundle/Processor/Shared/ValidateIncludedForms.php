<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Form\FormValidationHandler;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Performs the validation of forms for all included entities.
 */
class ValidateIncludedForms implements ProcessorInterface
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

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // the context does not have included entities
            return;
        }

        $form = $context->getForm();
        if (null === $form || !$form->isSubmitted()) {
            // the form for the primary entity does not exist or not submitted yet
            return;
        }

        foreach ($includedEntities as $includedEntity) {
            $includedForm = $includedEntities->getData($includedEntity)->getForm();
            if (null !== $includedForm && $includedForm->isSubmitted()) {
                $this->validator->validate($includedForm);
            }
        }
    }
}
