<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Provides a set of static methods that may be helpful in Data API form processing.
 */
class FormUtil
{
    /**
     * Checks whether the form is submitted and does not have errors.
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    public static function isSubmittedAndValid(FormInterface $form): bool
    {
        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * Checks whether the form is not submitted or submitted and does not have errors.
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    public static function isNotSubmittedOrSubmittedAndValid(FormInterface $form): bool
    {
        return !$form->isSubmitted() || $form->isValid();
    }

    /**
     * Adds a general error to a form.
     *
     * @param FormInterface $form
     * @param string        $errorMessage
     * @param string|null   $propertyPath
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
     *
     * @param FormInterface $form
     * @param string        $errorType
     * @param string        $errorMessage
     * @param string|null   $propertyPath
     */
    public static function addNamedFormError(
        FormInterface $form,
        string $errorType,
        string $errorMessage,
        string $propertyPath = null
    ): void {
        self::addFormConstraintViolation(
            $form,
            new NamedValidationConstraint($errorType),
            $errorMessage,
            $propertyPath
        );
    }

    /**
     * Adds constraint violation to a form.
     *
     * @param FormInterface $form
     * @param Constraint    $constraint
     * @param string|null   $errorMessage
     * @param string|null   $propertyPath
     */
    public static function addFormConstraintViolation(
        FormInterface $form,
        Constraint $constraint,
        string $errorMessage = null,
        string $propertyPath = null
    ): void {
        if (!$errorMessage && \property_exists($constraint, 'message')) {
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
     * Finds a form field by its property path.
     *
     * @param FormInterface $form
     * @param string        $propertyPath
     *
     * @return FormInterface|null
     */
    public static function findFormFieldByPropertyPath(FormInterface $form, string $propertyPath): ?FormInterface
    {
        $foundField = null;
        $fieldsWithoutPropertyPath = [];
        /** @var FormInterface $field */
        foreach ($form as $field) {
            $fieldPropertyPath = $field->getPropertyPath();
            if (null !== $fieldPropertyPath && (string)$fieldPropertyPath === $propertyPath) {
                $foundField = $field;
                break;
            }
        }
        if (null === $foundField) {
            foreach ($fieldsWithoutPropertyPath as $field) {
                if ($field->getName() === $propertyPath) {
                    $foundField = $field;
                    break;
                }
            }
        }

        return $foundField;
    }

    /**
     * @param ConstraintViolation $violation
     *
     * @return FormError
     */
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

    /**
     * @param FormInterface $form
     * @param string        $propertyPath
     *
     * @return string
     */
    private static function resolvePropertyPath(FormInterface $form, string $propertyPath): string
    {
        $path = [];
        while (null !== $form->getParent()) {
            $path[] = $form->getName();
            $form = $form->getParent();
        }
        $path = \array_merge(\array_reverse($path), \explode('.', $propertyPath));
        $path = \array_map(
            function ($item) {
                return \sprintf('children[%s]', $item);
            },
            $path
        );

        return \implode('.', $path);
    }
}
