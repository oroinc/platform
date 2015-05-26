<?php

namespace Oro\Bundle\LDAPBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListener
{

    /** @var ServiceLink */
    private $channelManagerLink;

    /** @var array */
    protected $synchronizedFields = [];

    /** @var array */
    protected $entitiesToUpdate = [];

    public function __construct(ServiceLink $channelManagerLink)
    {
        $this->channelManagerLink = $channelManagerLink;
    }

    /**
     * Happens after entity gets flushed.
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        /** @var UnitOfWork $uow */
        $uow = $args->getEntityManager()->getUnitOfWork();

        foreach ($this->entitiesToUpdate as $entity) {
            $mappings = (array)$entity->getLdapMappings();

            foreach ($mappings as $channel => $dn) {
                $changedFields = array_keys($uow->getEntityChangeSet($entity));
                if (!array_intersect(
                    $this->channelManagerLink->getService()->getLdapManager($channel)->getSynchronizedFields(),
                    $changedFields
                )) {
                    continue;
                }

                $this->channelManagerLink->getService()->save($entity, $channel);
            }
        }

        $this->entitiesToUpdate = [];
    }

    /**
     * Happens before flush.
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->processEntity($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->processEntity($entity);
        }
    }

    /**
     * Processes entity. Makes it available for export after it gets flushed.
     *
     * @param object $entity
     */
    public function processEntity($entity)
    {
        if (!$entity instanceof User) {
            return;
        }

        if (empty($entity->getLdapMappings())) {
            return;
        }

        $this->entitiesToUpdate[] = $entity;
    }
}
