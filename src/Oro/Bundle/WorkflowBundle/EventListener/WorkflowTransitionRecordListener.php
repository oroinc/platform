<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Send notifications on workflow transitions
 */
class WorkflowTransitionRecordListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    public function __construct(EventDispatcherInterface $eventDispatcher, TokenStorageInterface $tokenStorage)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenStorage = $tokenStorage;
    }

    public function postPersist(WorkflowTransitionRecord $transitionRecord, LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $this->eventDispatcher->dispatch(
            $this->getNotificationEvent($transitionRecord),
            WorkflowEvents::NOTIFICATION_TRANSIT_EVENT
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
