<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Provides a set of static methods that may be helpful in API form processing.
 */
class FormUtil
{
    /**
     * Gets API context within the given form is processed.
     */
    public static function getApiContext(FormInterface $form): ?FormContext
    {
        $context = $form->getRoot()->getConfig()->getOption(CustomizeFormDataHandler::API_CONTEXT);

        return $context instanceof FormContext
            ? $context
            : null;
    }

    /**
     * Checks whether the form is submitted and does not have errors.
     */
    public static function isSubmittedAndValid(FormInterface $form): bool
    {
        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * Checks whether the form is not submitted or submitted and does not have errors.
     */
    public static function isNotSubmittedOrSubmittedAndValid(FormInterface $form): bool
    {
        return !$form->isSubmitted() || $form->isValid();
    }

    /**
     * Makes sure that a form associated with the given property is submitted.
     */
    public static function ensureFieldSubmitted(
        FormInterface $form,
        string $propertyName,
        EntityDefinitionConfig $config = null
    ): void {
        $fieldName = $config?->findFieldNameByPropertyPath($propertyName);
        if (null === $fieldName) {
            $fieldName = $propertyName;
        }
        if ($form->has($fieldName)) {
            $fieldForm = $form->get($fieldName);
            if (!$fieldForm->isSubmitted()) {
                self::markAsSubmitted($fieldForm);
            }
        }
    }

    /**
     * Marks the given form as submitted.
     *
     * @throws \LogicException the the form was already submitted
     */
    public static function markAsSubmitted(FormInterface $form): void
    {
        if ($form->isSubmitted()) {
            throw new \LogicException(sprintf('The form "%s" was already submitted.', self::getFormPath($form)));
        }
        $markClosure = \Closure::bind(
            function ($form) {
                $form->submitted = true;
            },
            null,
            $form
        );
        $markClosure($form);
    }

    /**
     * Creates an instance of TransformationFailedException.
     */
    public static function createTransformationFailedException(
        string $message,
        string $invalidMessage = null,
        array $invalidMessageParameters = null
    ): TransformationFailedException {
        $result = new TransformationFailedException($message);
        if ($invalidMessage) {
            $result->setInvalidMessage($invalidMessage, $invalidMessageParameters ?? []);
        }

        return $result;
    }

    /**
     * Adds a general error to a form.
     */
    public static function addFormError(
        FormInterface $form,
        string $errorMessage,
        string $propertyPath = null
    ): void {
        if (null === $propertyPath) {
            $form->addError(new FormError($errorMessage));
        } else {
            $propertyPath = self::resolvePropertyPath($form, $propertyPath);
            $violation = new ConstraintViolation($errorMessage, null, [], '', $propertyPath, '');
            $form->addError(self::createFormError($violation));
        }
    }

    /**
     * Adds an error to a form.
     */
    public static function addNamedFormError(
        FormInterface $form,
        string $errorType,
        string $errorMessage,
        string $propertyPath = null,
        int $statusCode = null
    ): void {
        self::addFormConstraintViolation(
            $form,
            new NamedValidationConstraint($errorType, $statusCode),
            $errorMessage,
            $propertyPath
        );
    }

    /**
     * Adds constraint violation to a form.
     */
    public static function addFormConstraintViolation(
        FormInterface $form,
        Constraint $constraint,
        string $errorMessage = null,
        string $propertyPath = null
    ): void {
        if (!$errorMessage && property_exists($constraint, 'message')) {
            $errorMessage = $constraint->message;
        }
        if (null === $propertyPath) {
            $propertyPath = '';
        } else {
            $propertyPath = self::resolvePropertyPath($form, $propertyPath);
        }
        $violation = new ConstraintViolation($errorMessage, null, [], '', $propertyPath, '', null, null, $constraint);
        $form->addError(self::createFormError($violation));
    }

    /**
     * Removes {@see \Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted} validation constraint violation
     * for a form field.
     */
    public static function removeAccessGrantedValidationConstraint(FormInterface $form, string $propertyPath): void
    {
        $field = self::findFormFieldByPropertyPath($form, $propertyPath);
        if (null === $field) {
            return;
        }

        $constraints = $field->getConfig()->getOption('constraints');
        if (!$constraints) {
            return;
        }

        $constraintIndex = null;
        foreach ($constraints as $index => $constraint) {
            if ($constraint instanceof AccessGranted
                || (
                    $constraint instanceof All
                    && \count($constraint->constraints) === 1
                    && $constraint->constraints[0] instanceof AccessGranted
                )
            ) {
                $constraintIndex = $index;
                break;
            }
        }
        if (null === $constraintIndex) {
            return;
        }

        unset($constraints[$constraintIndex]);
        $constraints = array_values($constraints);

        self::setFormOption($field, 'constraints', $constraints);
    }

    /**
     * Finds a form field by its property path.
     */
    public static function findFormFieldByPropertyPath(FormInterface $form, string $propertyPath): ?FormInterface
    {
        return FormUtils::findFormField($form, $propertyPath);
    }

    /**
     * Asserts the given form is a root form and it is already submitted.
     *
     * @throws \InvalidArgumentException if the given form is not root form or it is not submitted yet
     */
    public static function assertSubmittedRootForm(FormInterface $form): void
    {
        if (!$form->isRoot()) {
            throw new \InvalidArgumentException('The root form is expected.');
        }
        if (!$form->isSubmitted()) {
            throw new \InvalidArgumentException('The submitted form is expected.');
        }
    }

    /**
     * Fixes property paths for validation errors related to properties expanded in API.
     * An example of a property expanded in API is a price. In this case an entity has
     * a "price" property that is an object with "value" and "currency" properties
     * and these properties are represented in API as separate attributes
     * and "price" property is not represented in API at all.
     * In this case all validation errors related to the "price" property should be moved to
     * "value" and "currency" API attributes, or in other words the ".price" prefix
     * should be removed from such validation errors.
     * It is required to return a correct error source pointer in API response.
     */
    public static function fixValidationErrorPropertyPathForExpandedProperty(
        FormInterface $form,
        string $propertyName
    ): void {
        $errors = $form->getErrors();
        foreach ($errors as $error) {
            $cause = $error->getCause();
            if (!$cause instanceof ConstraintViolation) {
                continue;
            }
            $path = '.' . $propertyName . '.';
            if (str_starts_with($cause->getPropertyPath(), 'data' . $path)) {
                $property = ReflectionUtil::getProperty(new \ReflectionClass($cause), 'propertyPath');
                if (null !== $property) {
                    $property->setAccessible(true);
                    $property->setValue($cause, str_replace($path, '.', $cause->getPropertyPath()));
                }
            }
        }
    }

    private static function createFormError(ConstraintViolation $violation): FormError
    {
        return new FormError(
            $violation->getMessage(),
            $violation->getMessageTemplate(),
            $violation->getParameters(),
            $violation->getPlural(),
            $violation
        );
    }

    private static function resolvePropertyPath(FormInterface $form, string $propertyPath): string
    {
        $path = [];
        while (null !== $form->getParent()) {
            $path[] = $form->getName();
            $form = $form->getParent();
        }
        $path = array_merge(array_reverse($path), explode('.', $propertyPath));
        $path = array_map(
            function ($item) {
                return sprintf('children[%s]', $item);
            },
            $path
        );

        return implode('.', $path);
    }

    private static function getFormPath(FormInterface $form): string
    {
        $path = [];
        $current = $form;
        while (null !== $current) {
            $name = $current->getName();
            if ($name) {
                $path[] = $name;
            }
            $current = $current->getParent();
        }

        return implode('.', array_reverse($path));
    }

    private static function setFormOption(FormInterface $form, string $optionName, mixed $optionValue): void
    {
        $fieldConfig = $form->getConfig();
        $options = $fieldConfig->getOptions();
        $options[$optionName] = $optionValue;
        $optionsProperty = ReflectionUtil::getProperty(new \ReflectionClass($fieldConfig), 'options');
        if (null === $optionsProperty) {
            throw new \RuntimeException(sprintf(
                'The class "%s" does not have property "options".',
                \get_class($fieldConfig)
            ));
        }
        $optionsProperty->setAccessible(true);
        $optionsProperty->setValue($fieldConfig, $options);
    }
}
