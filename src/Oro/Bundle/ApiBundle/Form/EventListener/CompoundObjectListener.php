<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This listener is intended to reset value of a field bound to CompoundObjectType form type.
 */
class CompoundObjectListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $submittedData = $event->getData();
        if (null === $submittedData) {
            $submittedData = [];
            $form = $event->getForm();
            foreach ($form as $name => $child) {
                $submittedData[$name] = null;
            }
            $event->setData($submittedData);
        }
    }
}
