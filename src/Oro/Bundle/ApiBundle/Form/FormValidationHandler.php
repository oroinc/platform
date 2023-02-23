<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataEventDispatcher;
use Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Provides functionality to validate any root form.
 */
class FormValidationHandler
{
    private ValidatorInterface $validator;
    private CustomizeFormDataEventDispatcher $customizeFormDataEventDispatcher;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        ValidatorInterface $validator,
        CustomizeFormDataEventDispatcher $customizeFormDataEventDispatcher,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->validator = $validator;
        $this->customizeFormDataEventDispatcher = $customizeFormDataEventDispatcher;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Dispatches "pre_validate" event for the given form.
     *
     * @throws \InvalidArgumentException if the given form is not root form or it is not submitted yet
     */
    public function preValidate(FormInterface $form): void
    {
        $this->customizeFormDataEventDispatcher->dispatch(CustomizeFormDataContext::EVENT_PRE_VALIDATE, $form);
    }

    /**
     * Validates the given form.
     *
     * @throws \InvalidArgumentException if the given form is not root form or it is not submitted yet
     */
    public function validate(FormInterface $form): void
    {
        FormUtil::assertSubmittedRootForm($form);

        /**
         * Mark all children of the processing root form as submitted
         * before start the form validation.
         * Using Form::submit($clearMissing = false) with the setting all fields as submitted
         * is a bit better approach than
         * using Form::submit($clearMissing = true) with a "pre-submit" listener that replaces missing fields
         * in submitted data with its default values from an entity.
         * Using the first approach we can submit all API forms
         * with $clearMissing = false and manage the validation just via "enable_full_validation" form option.
         * @link https://symfony.com/doc/current/form/direct_submit.html
         * @see  \Symfony\Component\Form\Form::submit
         * @link https://github.com/symfony/symfony/pull/10567
         * @see  \Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper::acceptsErrors
         * @see  \Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener
         * @see  \Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension
         */
        if ($form->getConfig()->getOption(ValidationExtension::ENABLE_FULL_VALIDATION)) {
            ReflectionUtil::markFormChildrenAsSubmitted($form, $this->propertyAccessor);
        }
        $this->getValidationListener()->validateForm(new FormEvent($form, $form->getViewData()));
    }

    /**
     * Dispatches "post_validate" event for the given form.
     *
     * @throws \InvalidArgumentException if the given form is not root form or it is not submitted yet
     */
    public function postValidate(FormInterface $form): void
    {
        $this->customizeFormDataEventDispatcher->dispatch(CustomizeFormDataContext::EVENT_POST_VALIDATE, $form);
    }

    private function getValidationListener(): ValidationListener
    {
        return new ValidationListener($this->validator, new ViolationMapper());
    }
}
