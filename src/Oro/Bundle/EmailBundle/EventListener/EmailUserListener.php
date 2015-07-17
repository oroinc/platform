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
    protected $insertedEmailUsersEntities = [];

    public function __construct(WebSocketSendProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->collectEmailUserEntities($uow->getScheduledEntityInsertions());
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $usersWithNewEmails = [];

        if (!$this->insertedEmailUsersEntities) {
            return;
        }

        /** @var EmailUser $insertedEntity */
        foreach ($this->insertedEmailUsersEntities as $insertedEntity) {
            if (!$insertedEntity->getOwner() || $insertedEntity->isSeen()) {
                //continue;
            }

            $usersWithNewEmails[$insertedEntity->getOwner()->getId()] = $insertedEntity->getOwner();
        }

        if ($usersWithNewEmails) {
            $this->processor->send($usersWithNewEmails);
        }

        $this->insertedEmailUsersEntities = [];
    }

    /**
     * Collect EmailUser entities
     *
     * @param array $entities
     */
    protected function collectEmailUserEntities($entities)
    {
        if ($entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof EmailUser) {
                    $this->insertedEmailUsersEntities[] = $entity;
                }
            }
        }
    }
}
