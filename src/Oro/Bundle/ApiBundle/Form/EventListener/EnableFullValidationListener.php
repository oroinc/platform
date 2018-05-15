<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Oro\Bundle\ApiBundle\Form\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * The listener that marks all children of the processing root form as submitted
 * before the form validation is started.
 * Using Form::submit($clearMissing = false) and this listener is a bit better approach than
 * using Form::submit($clearMissing = true) and a "pre-submit" listener that replaces missing fields
 * in submitted data with its default values from an entity, because in this case we can submit all Data API forms
 * with $clearMissing = false and manage the validation just adding this listener to the form builder.
 * @link https://symfony.com/doc/current/form/direct_submit.html
 * @see \Symfony\Component\Form\Form::submit
 * @link https://github.com/symfony/symfony/pull/10567
 * @see \Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper::acceptsErrors
 * @see \Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener
 */
class EnableFullValidationListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            /**
             * this method should be executed before ValidationListener::validateForm
             * @see \Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener::getSubscribedEvents
             */
            FormEvents::POST_SUBMIT => ['preValidateForm', 1]
        ];
    }

    /**
     * Marks all children of the processing root form as submitted.
     *
     * @param FormEvent $event The event object
     */
    public function preValidateForm(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->isRoot() && $form->isSubmitted()) {
            ReflectionUtil::markFormChildrenAsSubmitted($form);
        }
    }
}
