<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractorInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationPath;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Collects errors occurred during submit of forms for primary and included entities
 * and adds them into the context.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CollectFormErrors implements ProcessorInterface
{
    public const OPERATION_NAME = 'collect_form_errors';

    /** @var ConstraintTextExtractorInterface */
    protected $constraintTextExtractor;

    /** @var ErrorCompleterRegistry */
    protected $errorCompleterRegistry;

    /**
     * @param ConstraintTextExtractorInterface $constraintTextExtractor
     * @param ErrorCompleterRegistry           $errorCompleterRegistry
     */
    public function __construct(
        ConstraintTextExtractorInterface $constraintTextExtractor,
        ErrorCompleterRegistry $errorCompleterRegistry
    ) {
        $this->constraintTextExtractor = $constraintTextExtractor;
        $this->errorCompleterRegistry = $errorCompleterRegistry;
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

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the form errors were already collected
            return;
        }

        $this->collectFormErrors($context);
        $this->collectIncludedFormErrors($context);
        $context->setProcessed(self::OPERATION_NAME);
    }

    /**
     * @param FormContext $context
     */
    protected function collectFormErrors(FormContext $context): void
    {
        $form = $context->getForm();
        if (null !== $form && $form->isSubmitted() && !$form->isValid()) {
            $this->processForm($form, $context);
        }
    }

    /**
     * @param FormContext $context
     */
    protected function collectIncludedFormErrors(FormContext $context): void
    {
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

        $requestType = $context->getRequestType();
        $errorCompleter = $this->errorCompleterRegistry->getErrorCompleter($requestType);
        $errors = $context->getErrors();
        $context->resetErrors();
        foreach ($includedEntities as $includedEntity) {
            $includedData = $includedEntities->getData($includedEntity);
            $includedForm = $includedData->getForm();
            if (null !== $includedForm && $includedForm->isSubmitted() && !$includedForm->isValid()) {
                $this->processForm($includedForm, $context);
                if ($context->hasErrors()) {
                    foreach ($context->getErrors() as $error) {
                        $errorCompleter->complete($error, $requestType, $includedData->getMetadata());
                        $this->fixIncludedEntityErrorPath($error, $includedData->getPath());
                        $errors[] = $error;
                    }
                    $context->resetErrors();
                }
            }
        }
        $context->resetErrors();
        foreach ($errors as $error) {
            $context->addError($error);
        }
    }

    /**
     * @param Error  $error
     * @param string $entityPath
     */
    protected function fixIncludedEntityErrorPath(Error $error, string $entityPath): void
    {
        // no default implementation, the path to an included entity depends on the request type
    }

    /**
     * @param FormInterface $form
     * @param FormContext   $context
     */
    protected function processForm(FormInterface $form, FormContext $context): void
    {
        // collect errors of the root form
        $errors = $form->getErrors();
        foreach ($errors as $error) {
            $errorObject = $this->createErrorObject(
                $error,
                $this->getFormErrorPropertyPath($error)
            );
            $context->addError($errorObject);
        }

        // collect errors of child forms
        $this->processChildren($form, $context);
    }

    /**
     * @param FormInterface $form
     * @param FormContext   $context
     */
    protected function processChildren(FormInterface $form, FormContext $context): void
    {
        /** @var FormInterface $child */
        foreach ($form as $child) {
            if ($child->isSubmitted() && !$child->isValid()) {
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
    protected function getFormErrorPropertyPath(FormError $error): ?string
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
    protected function getFieldErrorPropertyPath(FormError $error, FormInterface $field): ?string
    {
        $result = null;

        $cause = $error->getCause();
        if ($cause instanceof ConstraintViolation) {
            $path = $this->getFormFieldPath($field);
            $causePath = $this->getConstraintViolationPath($cause);
            if (\count($causePath) > \count($path)) {
                $path = $causePath;
            }
        } else {
            $path = [$field->getName()];
            $parent = $field->getParent();
            while (null !== $parent && !$parent->isRoot()) {
                $path[] = $parent->getName();
                $parent = $parent->getParent();
            }
            $path = \array_reverse($path);
        }

        return \implode('.', $path);
    }

    /**
     * @param ConstraintViolation $constraintViolation
     *
     * @return string|null
     */
    protected function getConstraintViolationPropertyPath(ConstraintViolation $constraintViolation): ?string
    {
        $path = $this->getConstraintViolationPath($constraintViolation);

        return !empty($path)
            ? \implode('.', $path)
            : null;
    }

    /**
     * @param ConstraintViolation $constraintViolation
     *
     * @return string[]
     */
    protected function getConstraintViolationPath(ConstraintViolation $constraintViolation): array
    {
        $propertyPath = $constraintViolation->getPropertyPath();
        if (!$propertyPath) {
            return [];
        }

        $path = new ViolationPath($propertyPath);

        return $path->getElements();
    }

    /**
     * @param FormInterface $field
     *
     * @return string[]
     */
    protected function getFormFieldPath(FormInterface $field): array
    {
        $path = [];
        while (null !== $field->getParent()) {
            $path[] = $field->getName();
            $field = $field->getParent();
        }

        return \array_reverse($path);
    }

    /**
     * @param FormError   $formError
     * @param string|null $propertyPath
     *
     * @return Error
     */
    protected function createErrorObject(FormError $formError, string $propertyPath = null): Error
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
    protected function getFormErrorTitle(FormError $formError): string
    {
        $cause = $formError->getCause();
        if ($cause instanceof ConstraintViolation) {
            if ($this->isExtraFieldsConstraint($cause)) {
                // special case "extra fields" constraint
                // see comments of "isExtraFieldsConstraint" method for more details
                return Constraint::EXTRA_FIELDS;
            }

            return $this->constraintTextExtractor->getConstraintType($cause->getConstraint());
        }

        // undefined constraint type
        return Constraint::FORM;
    }

    /**
     * @param FormError $formError
     *
     * @return int|null
     */
    protected function getFormErrorStatusCode(FormError $formError): ?int
    {
        $cause = $formError->getCause();
        if ($cause instanceof ConstraintViolation) {
            return $this->constraintTextExtractor->getConstraintStatusCode($cause->getConstraint());
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
    protected function isExtraFieldsConstraint(ConstraintViolation $cause): bool
    {
        $parameters = $cause->getParameters();

        return \array_key_exists('{{ extra_fields }}', $parameters) && 1 === \count($parameters);
    }
}
