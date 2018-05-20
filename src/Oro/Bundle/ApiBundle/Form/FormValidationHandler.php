<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Provides functionality to validate any root form.
 */
class FormValidationHandler
{
    /** @var ValidatorInterface */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validates the given form.
     *
     * @param FormInterface $form
     *
     * @throws \InvalidArgumentException if the given for is not root form or it is not submitted yet
     */
    public function validate(FormInterface $form): void
    {
        if (!$form->isRoot()) {
            throw new \InvalidArgumentException('The root form is expected.');
        }
        if (!$form->isSubmitted()) {
            throw new \InvalidArgumentException('The submitted form is expected.');
        }

        $validationListener = $this->getValidationListener();
        $validationListener->validateForm(new FormEvent($form, null));
    }

    /**
     * @return ValidationListener
     */
    protected function getValidationListener(): ValidationListener
    {
        return new ValidationListener(
            $this->validator,
            new ViolationMapper()
        );
    }
}
