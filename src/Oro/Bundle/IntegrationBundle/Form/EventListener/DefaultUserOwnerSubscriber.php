<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class DefaultUserOwnerSubscriber implements EventSubscriberInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::POST_SET_DATA => 'postSet'];
    }

    /**
     * Sets default data for create integrations form
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $data = $event->getData();

        if ($data && !$data->getId() && !$data->getDefaultUserOwner() || null === $data) {
            $event->getForm()->get('defaultUserOwner')->setData($this->securityFacade->getLoggedUser());
        }
    }
}
