<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles user's imap settings by passing a currently configured user to the form and updating configuration form
 * with data from ajax-generated form.
 */
class UserImapConfigSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private RequestStack $requestStack,
        private TokenAccessorInterface $tokenAccessor
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSubmit(FormEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $data = $request->get('oro_user_emailsettings');
            if ($data) {
                $eventData = (array) $event->getData();
                //update configuration form with data from ajax-generated form
                $event->setData(array_merge_recursive($eventData, $data));
            }
        }
    }

    /**
     * Pass currently configured user to the form
     */
    public function preSetData(FormEvent $event)
    {
        if (!$event->getData()) {
            $user = $this->getUser();
            $event->setData($user);
        }
    }

    /**
     * Get configured user
     */
    private function getUser(): ?User
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = null;
        if ($request) {
            $currentRoute = $request->attributes->get('_route');
            if ($currentRoute === 'oro_user_config') {
                $id = $request->attributes->getInt('id');
                $user = $this->doctrine->getManagerForClass(User::class)->find(User::class, $id);
            } elseif ($currentRoute === 'oro_user_profile_configuration') {
                $user = $this->tokenAccessor->getUser();
            }
        }
        if ($user && $user->getCurrentOrganization() === null) {
            $user->setCurrentOrganization($this->tokenAccessor->getOrganization());
        }

        return $user;
    }
}
