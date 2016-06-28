<?php

namespace Oro\Bundle\UserBundle\Form\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\UserBundle\Entity\User;

class UserImapConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /** @var RequestStack */
    protected $requestStack;


    /**
     * @param ObjectManager $manager
     * @param RequestStack $requestStack
     * @param SecurityContextInterface  $security
     */
    public function __construct(
        ObjectManager $manager,
        RequestStack $requestStack,
        SecurityContextInterface $security
    ) {
        $this->manager = $manager;
        $this->requestStack = $requestStack;
        $this->security = $security;
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
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var User $user */
        $user = $event->getData();
        if ($user) {
            $this->manager->persist($user);
            $this->manager->flush();
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
                //update configuration form with data from ajax-generated form
                $event->setData($data);
            }
        }
    }

    /**
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
                $user = $this->manager->find('OroUserBundle:User', $id);
            } elseif ($currentRoute === 'oro_user_profile_configuration') {
                $token = $this->security->getToken();
                $user = $token ? $token->getUser() : null;
            }
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
