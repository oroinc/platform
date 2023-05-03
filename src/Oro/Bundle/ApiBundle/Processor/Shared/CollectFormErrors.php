<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractorInterface;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationPath;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Collects errors occurred when submitting forms for primary and included entities
 * and adds them into the context.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CollectFormErrors implements ProcessorInterface
{
    public const OPERATION_NAME = 'collect_form_errors';

    protected ConstraintTextExtractorInterface $constraintTextExtractor;
    protected ErrorCompleterRegistry $errorCompleterRegistry;
    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        ConstraintTextExtractorInterface $constraintTextExtractor,
        ErrorCompleterRegistry $errorCompleterRegistry,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->constraintTextExtractor = $constraintTextExtractor;
        $this->errorCompleterRegistry = $errorCompleterRegistry;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
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

    protected function collectFormErrors(FormContext $context): void
    {
        $form = $context->getForm();
        if (null !== $form && $form->isSubmitted() && !$form->isValid()) {
            $this->processForm($form, $context);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
                        $errorCompleter->fixIncludedEntityPath(
                            $includedData->getPath(),
                            $error,
                            $requestType,
                            $includedData->getMetadata()
                        );
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

    protected function processForm(FormInterface $form, FormContext $context): void
    {
        /** @var Error[] $foundErrors */
        $foundErrors = [];
        $errors = $form->getErrors();
        foreach ($errors as $error) {
            $errorObject = $this->createErrorObject(
                $error,
                $this->getFormErrorPropertyPath($error)
            );
            $context->addError($errorObject);
            $foundErrors[] = [$form, $errorObject];
        }
        $this->processChildren($form, $context, $foundErrors);
        $this->fixErrorPaths($foundErrors, $context);
    }

    /**
     * @param FormInterface $form
     * @param FormContext   $context
     * @param array         $foundErrors [[parent form, error], ...]
     */
    protected function processChildren(FormInterface $form, FormContext $context, array &$foundErrors): void
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
                    $foundErrors[] = [$form, $errorObject];
                }
                if ($this->isCompoundForm($child)) {
                    $this->processChildren($child, $context, $foundErrors);
                }
            }
        }
    }

    /**
     * @param array       $foundErrors [[parent form, error], ...]
     * @param FormContext $context
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function fixErrorPaths(array $foundErrors, FormContext $context): void
    {
        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        $requestType = $context->getRequestType();
        /**
         * @var FormInterface $parentForm
         * @var Error         $error
         */
        foreach ($foundErrors as [$parentForm, $error]) {
            $errorSource = $error->getSource();
            if (null === $errorSource || !str_contains($errorSource->getPropertyPath(), ConfigUtil::PATH_DELIMITER)) {
                continue;
            }
            $path = ConfigUtil::explodePropertyPath($errorSource->getPropertyPath());
            $fieldForm = FormUtil::findFormFieldByPropertyPath($parentForm, $path[0]);
            if (null === $fieldForm) {
                continue;
            }
            $fieldData = $fieldForm->getData();
            if (!\is_object($fieldData)) {
                continue;
            }
            if (!$fieldData instanceof Collection) {
                $this->fixErrorPath($path[1], $error, $requestType, $includedEntities->getData($fieldData));
            } elseif (\count($path) > 2) {
                foreach ($fieldData as $key => $item) {
                    if ($path[1] === (string)$key && \is_object($item)) {
                        $this->fixErrorPath($path[2], $error, $requestType, $includedEntities->getData($item));
                    }
                }
            }
        }
    }

    protected function fixErrorPath(
        string $propertyPath,
        Error $error,
        RequestType $requestType,
        ?IncludedEntityData $includedData
    ): void {
        if (null === $includedData) {
            return;
        }

        $error->getSource()->setPropertyPath($propertyPath);
        $errorCompleter = $this->errorCompleterRegistry->getErrorCompleter($requestType);
        $errorCompleter->fixIncludedEntityPath(
            $includedData->getPath(),
            $error,
            $requestType,
            $includedData->getMetadata()
        );
    }

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

    protected function getFieldErrorPropertyPath(FormError $error, FormInterface $field): ?string
    {
        $cause = $error->getCause();
        if ($cause instanceof ConstraintViolation) {
            $path = $this->getFieldErrorPropertyPathByConstraintViolation($field, $cause);
        } else {
            $path = [$field->getName()];
            $parent = $field->getParent();
            while (null !== $parent && !$parent->isRoot()) {
                $path[] = $parent->getName();
                $parent = $parent->getParent();
            }
            $path = array_reverse($path);
        }

        return implode(ConfigUtil::PATH_DELIMITER, $path);
    }

    /**
     * Makes sure that an validation error path points to a correct element in a source collection.
     * It may happen that the order of elements is different in a source collection
     * and in a collection that was validated. E.g. due to the collection adder and remover methods
     * can keep a position of existing elements without changes.
     *
     * @param FormInterface       $field
     * @param ConstraintViolation $cause
     *
     * @return string[]
     */
    protected function getFieldErrorPropertyPathByConstraintViolation(
        FormInterface $field,
        ConstraintViolation $cause
    ): array {
        $path = $this->getConstraintViolationPath($cause);

        $fieldPath = $this->getFormFieldPath($field);
        if (\count($fieldPath) >= \count($path)) {
            return $fieldPath;
        }

        // check if the path represents a property of an item in a collection, e.g. collection.1.field
        if (\count($path) <= 2 || !is_numeric($path[1])) {
            return $path;
        }

        $sourceData = $field->getData();
        if (!$sourceData instanceof Collection) {
            return $path;
        }

        $parentForm = $field->getParent();
        if (null === $parentForm || $this->isCompoundForm($field)) {
            return $path;
        }

        $validatedData = $this->getPropertyValueFromFormData($parentForm, $path[0]);
        if (!$validatedData instanceof Collection) {
            return $path;
        }

        $path[1] = $this->resolveCollectionKey($path[1], $validatedData, $sourceData);

        return $path;
    }

    protected function resolveCollectionKey(string $key, Collection $source, Collection $target): string
    {
        if ($source->containsKey($key)) {
            $indexInTarget = $target->indexOf($source->get($key));
            if (false !== $indexInTarget) {
                $key = (string)$indexInTarget;
            }
        }

        return $key;
    }

    protected function getPropertyValueFromFormData(FormInterface $form, string $propertyName): mixed
    {
        $parentData = $form->getData();
        if (!\is_object($parentData) || !$this->propertyAccessor->isReadable($parentData, $propertyName)) {
            return null;
        }

        return $this->propertyAccessor->getValue($parentData, $propertyName);
    }

    protected function getConstraintViolationPropertyPath(ConstraintViolation $constraintViolation): ?string
    {
        $path = $this->getConstraintViolationPath($constraintViolation);

        return !empty($path)
            ? implode(ConfigUtil::PATH_DELIMITER, $path)
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

        return array_values(array_filter(
            $path->getElements(),
            function ($val) {
                return ConfigUtil::IGNORE_PROPERTY_PATH !== $val;
            }
        ));
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

        return array_reverse($path);
    }

    protected function isCompoundForm(FormInterface $field): bool
    {
        return $field->getConfig()->getCompound();
    }

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

    protected function getFormErrorTitle(FormError $formError): string
    {
        $cause = $formError->getCause();
        if ($cause instanceof ConstraintViolation) {
            if ($this->isExtraFieldsConstraint($cause)) {
                // special case "extra fields" constraint
                // see comments of "isExtraFieldsConstraint" method for more details
                return Constraint::EXTRA_FIELDS;
            }
            $constraint = $cause->getConstraint();
            if (null !== $constraint) {
                return $this->constraintTextExtractor->getConstraintType($constraint);
            }
        }

        // undefined constraint type
        return Constraint::FORM;
    }

    protected function getFormErrorStatusCode(FormError $formError): ?int
    {
        $cause = $formError->getCause();
        if ($cause instanceof ConstraintViolation) {
            $constraint = $cause->getConstraint();
            if (null !== $constraint) {
                return $this->constraintTextExtractor->getConstraintStatusCode($constraint);
            }
        }

        return null;
    }

    /**
     * Checks whether a given cause of a form error represents "extra fields" constraint.
     * We have to do this because this type of validation does not have own validator
     * and a validation is performed by main form validator.
     * @see \Symfony\Component\Form\Extension\Validator\Constraints\FormValidator::validate
     */
    protected function isExtraFieldsConstraint(ConstraintViolation $cause): bool
    {
        $parameters = $cause->getParameters();

        return \array_key_exists('{{ extra_fields }}', $parameters) && 1 === \count($parameters);
    }
}
