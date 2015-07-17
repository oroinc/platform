<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailUserListener
{
    /**
     * @var WebSocketSendProcessor
     */
    protected $processor;

    /**
     * @var array
     */
    protected $processEmailUsersEntities = [];

    public function __construct(WebSocketSendProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Collecting added EmailUser entities for processing in postFlush
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->collectNewEmailUserEntities($uow->getScheduledEntityInsertions());
        $this->collectUpdatedEmailUserEntities($uow->getScheduledEntityUpdates(), $uow);
    }

    /**
     * Send notification to clank that user have new emails
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $usersWithNewEmails = [];
        if (!$this->processEmailUsersEntities) {
            return;
        }

        /** @var EmailUser $entity */
        foreach ($this->processEmailUsersEntities as $entity) {
            if (!$entity->getOwner()) {
                continue;
            }
            $usersWithNewEmails[$entity->getOwner()->getId()] = $entity->getOwner();
        }
        if ($usersWithNewEmails) {
            $this->processor->send($usersWithNewEmails);
        }
        $this->processEmailUsersEntities = [];
    }

    /**
     * Collect new EmailUser entities
     *
     * @param array $entities
     */
    protected function collectNewEmailUserEntities($entities)
    {
        if ($entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof EmailUser && !$entity->isSeen()) {
                    $this->processEmailUsersEntities[] = $entity;
                }
            }
        }
    }

    /**
     * Collect updated EmailUser entities
     *
     * @param array $entities
     */
    protected function collectUpdatedEmailUserEntities($entities, $uow)
    {
        if ($entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof EmailUser) {
                    $changes = $uow->getEntityChangeSet($entity);
                    if (array_key_exists('seen', $changes) === true) {
                        $this->processEmailUsersEntities[] = $entity;
                    }
                }
            }
        }
    }
}
