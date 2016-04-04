<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Model\Error;

/**
 * Collects errors occurred during the the form submit and adds them into the Context.
 */
class CollectFormErrors implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var $context FormContext */

        if (!$context->hasForm()) {
            // no form
            return;
        }

        $form = $context->getForm();
        if (!$form->isSubmitted()) {
            // the form is not submitted
            return;
        }
        if ($form->isValid()) {
            // the form does not have errors
            return;
        }

        // collect form global errors
        foreach ($form->getErrors() as $error) {
            $errorObject = $this->createErrorObject(
                $error->getMessage(),
                $error->getOrigin()->getName()
            );
            $context->addError($errorObject);
        }

        // collect form childes errors
        foreach ($form as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errorObject = $this->createErrorObject(
                        $error->getMessage(),
                        $child->getName()
                    );
                    $context->addError($errorObject);
                }
            }
        }
    }

    /**
     * @param string $errorMessage
     * @param string $propertyPath
     *
     * @return Error
     */
    protected function createErrorObject($errorMessage, $propertyPath)
    {
        $error = new Error();
        $error->setDetail($errorMessage);
        $error->setPropertyName($propertyPath);

        return $error;
    }
}
