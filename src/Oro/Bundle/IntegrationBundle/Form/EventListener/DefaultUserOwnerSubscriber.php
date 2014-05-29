<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\UserBundle\Entity\User;

class DefaultUserOwnerSubscriber implements EventSubscriberInterface
{
    /** @var SecurityContextInterface */
    protected $securityContext;

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::POST_SET_DATA => 'postSet'];
    }

    /**
     * Sets default data for create channels form
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $data = $event->getData();

        if ($data && !$data->getId() && !$data->getDefaultUserOwner() || null === $data) {
            $event->getForm()->get('defaultUserOwner')->setData($this->getCurrentUser());
        }
    }

    /**
     * Returns current logged in user
     *
     * @return User|null
     */
    protected function getCurrentUser()
    {
        return $this->securityContext->getToken() && !is_string($this->securityContext->getToken()->getUser())
            ? $this->securityContext->getToken()->getUser() : null;
    }
}
