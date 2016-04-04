<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Form\Form;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Collects form errors occurred due create or update requests and adds them into context.
 */
class CollectFormErrors implements ProcessorInterface
{
    public function process(ContextInterface $context)
    {
        /** @var $context FormContext */

        if (false === $context->hasForm()) {
            throw new \RuntimeException('The form must be set in the context.');
        }

        /** @var Form $form */
        $form = $context->getForm();

        if (false === $form->isSubmitted()) {
            throw new \RuntimeException('The form must be submitted.');
        }

        if ($form->isValid()) {
            // form valid, nothing to do
            return;
        }

        // collect form global errors
        foreach ($form->getErrors() as $error) {
            $errorObject = new Error();

            $errorObject->setPropertyName($error->getOrigin()->getName());
            $errorObject->setDetail($error->getMessage());

            $context->addError($errorObject);
        }

        // collect form childes errors
        foreach ($form as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errorObject = new Error();

                    $errorObject->setPropertyName($child->getName());
                    $errorObject->setDetail($error->getMessage());

                    $context->addError($errorObject);
                }
            }
        }
    }
}
