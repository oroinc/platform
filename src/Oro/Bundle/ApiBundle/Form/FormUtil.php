<?php

namespace Oro\Bundle\ApiBundle\Form;

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
     * Creates an instance of TransformationFailedException.
     *
     * @param string      $message
     * @param string|null $invalidMessage
     * @param array|null  $invalidMessageParameters
     *
     * @return TransformationFailedException
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
        if ($form->has($propertyPath)) {
            $child = $form->get($propertyPath);
            $childPropertyPath = $child->getPropertyPath();
            if (null === $childPropertyPath || (string)$childPropertyPath === $propertyPath) {
                return $child;
            }
        }

        /** @var FormInterface $child */
        foreach ($form as $child) {
            $childPropertyPath = $child->getPropertyPath();
            if (null === $childPropertyPath) {
                if ($child->getName() === $propertyPath) {
                    return $child;
                }
            } elseif ((string)$childPropertyPath === $propertyPath) {
                return $child;
            }
        }

        return null;
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
