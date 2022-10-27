<?php

namespace Oro\Bundle\AddressBundle\Form\EventListener;

use Oro\Bundle\AddressBundle\Entity\Address;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Event subscriber for setting address ID to non-mapped ID field
 */
class AddressIdentifierSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
        ];
    }

    /**
     * Setting address ID to ID field of the form
     */
    public function postSetData(FormEvent $event)
    {
        /** @var Address $address */
        $address = $event->getData();

        if (null === $address) {
            return;
        }

        $form = $event->getForm();
        if ($form->has('id')) {
            $form->get('id')->setData($address->getId());
        }
    }
}
