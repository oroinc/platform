<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationPath;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Validator\Constraints\ConstraintWithStatusCodeInterface;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

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
        /** @var FormContext $context */

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
                $error,
                $this->getFormErrorPropertyPath($error)
            );
            $context->addError($errorObject);
        }

        // collect form child errors
        $this->processChildren($form, $context);
    }

    /**
     * @param FormInterface $form
     * @param FormContext   $context
     */
    protected function processChildren(FormInterface $form, FormContext $context)
    {
        /** @var FormInterface $child */
        foreach ($form as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errorObject = $this->createErrorObject(
                        $error,
                        $this->getFieldErrorPropertyPath($error, $child)
                    );
                    $context->addError($errorObject);
                }
                if ($child->getConfig()->getCompound()) {
                    $this->processChildren($child, $context);
                }
            }
        }
    }

    /**
     * @param FormError $error
     *
     * @return string|null
     */
    protected function getFormErrorPropertyPath(FormError $error)
    {
        $result = null;

        $cause = $error->getCause();
        if ($cause instanceof ConstraintViolation) {
            $result = $this->getConstraintViolationPropertyPath($cause);
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
     * @param FormError     $error
     * @param FormInterface $field
     *
     * @return string|null
     */
    protected function getFieldErrorPropertyPath(FormError $error, FormInterface $field)
    {
        $result = null;

        $cause = $error->getCause();
        if ($cause instanceof ConstraintViolation) {
            $result = $this->getConstraintViolationPropertyPath($cause);
        }
        if (!$result) {
            $result = $field->getName();
        }

        return $result;
    }

    /**
     * @param ConstraintViolation $constraintViolation
     *
     * @return string|null
     */
    protected function getConstraintViolationPropertyPath(ConstraintViolation $constraintViolation)
    {
        $propertyPath = $constraintViolation->getPropertyPath();
        if (!$propertyPath) {
            return null;
        }

        $path = new ViolationPath($propertyPath);

        return implode('.', $path->getElements());
    }

    /**
     * @param FormError   $formError
     * @param string|null $propertyPath
     *
     * @return Error
     */
    protected function createErrorObject(FormError $formError, $propertyPath = null)
    {
        $error = Error::createValidationError($this->getFormErrorTitle($formError), $formError->getMessage());
        $statusCode = $this->getFormErrorStatusCode($formError);
        if (null !== $statusCode) {
            $error->setStatusCode($statusCode);
        }
        if ($propertyPath) {
            $error->setSource(ErrorSource::createByPropertyPath($propertyPath));
        }

        return $error;
    }

    /**
     * @param FormError $formError
     *
     * @return string
     */
    protected function getFormErrorTitle(FormError $formError)
    {
        $cause = $formError->getCause();
        if ($cause instanceof ConstraintViolation) {
            if ($this->isExtraFieldsConstraint($cause)) {
                // special case "extra fields" constraint
                // see comments of "isExtraFieldsConstraint" method for more details
                return Constraint::EXTRA_FIELDS;
            }

            return ValueNormalizerUtil::humanizeClassName(get_class($cause->getConstraint()), 'Constraint');
        }

        // undefined constraint type
        return Constraint::FORM;
    }

    /**
     * @param FormError $formError
     *
     * @return int|null
     */
    protected function getFormErrorStatusCode(FormError $formError)
    {
        $cause = $formError->getCause();
        if ($cause instanceof ConstraintViolation) {
            $constraint = $cause->getConstraint();
            if ($constraint instanceof ConstraintWithStatusCodeInterface) {
                return $constraint->getStatusCode();
            }
        }

        return null;
    }

    /**
     * Checks whether a given cause of a form error represents "extra fields" constraint.
     * We have to do this because this type of validation does not have own validator
     * and a validation is performed by main form validator.
     * @see \Symfony\Component\Form\Extension\Validator\Constraints\FormValidator::validate
     *
     * @param ConstraintViolation $cause
     *
     * @return bool
     */
    protected function isExtraFieldsConstraint(ConstraintViolation $cause)
    {
        $parameters = $cause->getParameters();

        return array_key_exists('{{ extra_fields }}', $parameters) && 1 === count($parameters);
    }
}
