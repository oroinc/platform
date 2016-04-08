<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Model\Error;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;

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
        $errors = $form->getErrors();
        foreach ($errors as $error) {
            $errorObject = $this->createErrorObject(
                $error->getMessage(),
                $this->getFormErrorPropertyName($error, $form)
            );
            $context->addError($errorObject);
        }

        // collect form childes errors
        /** @var FormInterface $child */
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
     * @param FormError     $error
     * @param FormInterface $form
     *
     * @return string|null
     */
    protected function getFormErrorPropertyName(FormError $error, FormInterface $form)
    {
        $result = null;

        $cause = $error->getCause();
        if ($cause instanceof ConstraintViolation) {
            $result = $cause->getPropertyPath();
            if (0 === strpos($result, 'data.')) {
                $result = substr($result, 5);
            }
            // in case if propertyPath = 'data', this error can be an entity level error
            if ($result === 'data' && !$form->has('data')) {
                $result = '';
            }
        }
        if (!$result) {
            $originName = $error->getOrigin()->getName();
            if ($originName) {
                $result = $originName;
            }
        }

        return $result;
    }

    /**
     * @param string      $errorMessage
     * @param string|null $propertyName
     *
     * @return Error
     */
    protected function createErrorObject($errorMessage, $propertyName = null)
    {
        $error = new Error();
        $error->setDetail($errorMessage);
        $error->setPropertyName($propertyName);
        $error->setStatusCode(Response::HTTP_BAD_REQUEST);

        return $error;
    }
}
