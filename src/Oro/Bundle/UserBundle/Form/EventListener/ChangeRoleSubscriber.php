<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This class updates users (append/remove) according to submitted data
 */
class ChangeRoleSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::SUBMIT => ['onSubmit', 10]];
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        /** @var AbstractRole $role */
        $role = $event->getData();

        if (!$role) {
            return;
        }

        $form = $event->getForm();

        /** @var AbstractUser $user */
        foreach ($form->get('appendUsers')->getData() as $user) {
            $user->addRole($role);
        }

        /** @var AbstractUser $user */
        foreach ($form->get('removeUsers')->getData() as $user) {
            $user->removeRole($role);
        }
    }
}
