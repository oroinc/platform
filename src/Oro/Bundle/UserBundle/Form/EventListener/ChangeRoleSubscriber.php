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
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [FormEvents::SUBMIT => ['onSubmit', 10]];
    }

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
            $user->addUserRole($role);
        }

        /** @var AbstractUser $user */
        foreach ($form->get('removeUsers')->getData() as $user) {
            $user->removeUserRole($role);
        }
    }
}
