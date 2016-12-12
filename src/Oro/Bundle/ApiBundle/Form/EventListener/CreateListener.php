<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Replaces missing items in submitted data with its default values.
 * This prevents to reset default values to NULL in "create" Data API action.
 */
class CreateListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => ['preSubmit', 255]
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $submittedData = $event->getData();
        if (is_array($submittedData)) {
            $this->updateSubmittedData($submittedData, $form);
            $event->setData($submittedData);
        }
    }

    /**
     * @param array         $submittedData
     * @param FormInterface $form
     */
    protected function updateSubmittedData(array &$submittedData, FormInterface $form)
    {
        /** @var FormInterface $child */
        foreach ($form as $name => $child) {
            if (!$child->getConfig()->getCompound() && !array_key_exists($name, $submittedData)) {
                $value = $child->getData();
                if (null !== $value && is_scalar($value)) {
                    /**
                     * as Symfony Form treats false as NULL due to checkboxes
                     * @see Symfony\Component\Form\Form::submit
                     * we have to convert false to its string representation here
                     */
                    if (false === $value) {
                        $value = 'false';
                    }
                    $submittedData[$name] = $value;
                }
            }
        }
    }
}
