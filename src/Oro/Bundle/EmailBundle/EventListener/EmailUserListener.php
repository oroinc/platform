<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\ActivityListBundle\Entity\Manager\CollectListManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
        $entities = $uow->getScheduledEntityInsertions();
        $this->addEmailUserEntities($entities);
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->insertedEmailUsersEntities) {
            return;
        }


    }

    protected function addEmailUserEntities($entities)
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
