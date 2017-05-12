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
    public static function isSubmittedAndValid(FormInterface $form)
    {
        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * Adds an error to a form.
     *
     * @param FormInterface $form
     * @param string        $errorMessage
     */
    public static function addFormError(FormInterface $form, $errorMessage)
    {
        $form->addError(new FormError($errorMessage));
    }

    /**
     * Adds constraint violation to a form.
     *
     * @param FormInterface $form
     * @param Constraint    $constraint
     * @param string|null   $errorMessage
     */
    public static function addFormConstraintViolation(
        FormInterface $form,
        Constraint $constraint,
        $errorMessage = null
    ) {
        if (!$errorMessage && property_exists($constraint, 'message')) {
            $errorMessage = $constraint->message;
        }
        $violation = new ConstraintViolation($errorMessage, null, [], '', '', '', null, null, $constraint);
        $form->addError(
            new FormError(
                $violation->getMessage(),
                $violation->getMessageTemplate(),
                $violation->getParameters(),
                $violation->getPlural(),
                $violation
            )
        );
    }
}
