<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Symfony\Component\Form\Form;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\SingleItemUpdateContext;

/**
 * Collects form errors occurred due create or update requests and adds them into context.
 */
class CollectFormErrors implements ProcessorInterface
{
    public function process(ContextInterface $context)
    {
        /** @var $context SingleItemUpdateContext */

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

        $formErrors = $form->getErrors(true, true);
        foreach ($formErrors as $error) {
            $errorObject = new Error();

            $errorObject->setPropertyName($error->getOrigin()->getName());
            $errorObject->setDetail($error->getMessage());

            $context->addError($errorObject);
        }
    }
}
