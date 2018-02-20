<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class UserImapConfigSubscriber implements EventSubscriberInterface
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param EntityManager          $entityManager
     * @param RequestStack           $requestStack
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * Save user with new imap configuration
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var User $user */
        $user = $event->getData();
        if ($user) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $request = $this->getRequest();
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
     * Pass currenlty configured user to the form
     *
     * @param FormEvent $event
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
     *
     * @return User|null
     */
    protected function getUser()
    {
        $request = $this->getRequest();
        $user = null;
        if ($request) {
            $currentRoute = $request->attributes->get('_route');
            if ($currentRoute === 'oro_user_config') {
                $id = $request->attributes->getInt('id');
                $user = $this->entityManager->find('OroUserBundle:User', $id);
            } elseif ($currentRoute === 'oro_user_profile_configuration') {
                $user = $this->tokenAccessor->getUser();
            }
        }
        if ($user && $user->getCurrentOrganization() === null) {
            $user->setCurrentOrganization($this->tokenAccessor->getOrganization());
        }

        return $user;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }
}
