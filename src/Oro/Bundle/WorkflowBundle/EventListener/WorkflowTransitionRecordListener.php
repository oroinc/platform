<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WorkflowTransitionRecordListener implements OptionalListenerInterface
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var bool */
    protected $enabled = true;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, TokenStorageInterface $tokenStorage)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = (bool)$enabled;
    }

    /**
     * @param WorkflowTransitionRecord $transitionRecord
     * @param LifecycleEventArgs       $args
     */
    public function postPersist(WorkflowTransitionRecord $transitionRecord, LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $this->eventDispatcher->dispatch(
            LoadWorkflowNotificationEvents::TRANSIT_EVENT,
            $this->getNotificationEvent($transitionRecord)
        );
    }

    /**
     * @param WorkflowTransitionRecord $transitionRecord
     * @return NotificationEvent
     */
    protected function getNotificationEvent(WorkflowTransitionRecord $transitionRecord)
    {
        $entity = $transitionRecord->getWorkflowItem()->getEntity();

        return new WorkflowNotificationEvent($entity, $transitionRecord, $this->getLoggedUser());
    }

    /**
     * @return null|AbstractUser
     */
    protected function getLoggedUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        return $user instanceof AbstractUser ? $user : null;
    }
}
