<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles form events for password change operations.
 *
 * This event subscriber manages the password change form lifecycle, including
 * validation of the current password and handling of empty password fields.
 * It ensures proper password field management during form submission.
 */
class ChangePasswordSubscriber extends UserSubscriber
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(
            FormEvents::POST_SUBMIT => 'onSubmit',
            FormEvents::PRE_SUBMIT   => 'preSubmit'
        );
    }

    /**
     * Re-create current password field in case of user don't filled any password field
     */
    #[\Override]
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $isEmptyPassword = $data['currentPassword'] . $data['plainPassword']['first'];
        $isEmptyPassword = empty($isEmptyPassword);

        if ($isEmptyPassword) {
            $form->remove('currentPassword');

            $form->add(
                $this->factory->createNamed(
                    'currentPassword',
                    PasswordType::class,
                    null,
                    array(
                        'auto_initialize' => false,
                        'mapped' => false,
                    )
                )
            );
        }
    }

    public function onSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var User $user */
        $user = $form->getParent()->getData();
        $plainPassword = $form->get('plainPassword');

        if ($this->isCurrentUser($user)) {
            $user->setPlainPassword($plainPassword->getData());
        }
    }
}
