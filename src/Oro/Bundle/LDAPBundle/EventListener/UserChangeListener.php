<?php

namespace Oro\Bundle\LDAPBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListener
{
    /** @var ServiceLink */
    protected $ldapManagerLink;

    /** @var array */
    protected $synchronizedFields = [];

    /** @var array */
    protected $entitiesToUpdate = [];

    /**
     * @param ServiceLink $ldapManagerLink
     */
    public function __construct(ServiceLink $ldapManagerLink)
    {
        $this->ldapManagerLink = $ldapManagerLink;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        array_map([$this->getLdapManager(), 'save'], $this->entitiesToUpdate);
        $this->entitiesToUpdate = [];
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->processEntity($entity, $uow);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->processEntity($entity, $uow);
        }
    }

    /**
     * @param object $entity
     * @param UnitOfWork $uow
     */
    public function processEntity($entity, UnitOfWork $uow)
    {
        if (!$entity instanceof User) {
            return;
        }

        $changedFields = array_keys($uow->getEntityChangeSet($entity));
        if (!array_intersect($this->getSynchronizedFields(), $changedFields)) {
            return;
        }

        $this->entitiesToUpdate[] = $entity;
    }

    /**
     * @return array
     */
    protected function getSynchronizedFields()
    {
        if (!$this->synchronizedFields) {
            $this->synchronizedFields = $this->getLdapManager()->getSynchronizedFields();
        }

        return $this->synchronizedFields;
    }

    /**
     * @return LdapManager
     */
    protected function getLdapManager()
    {
        return $this->ldapManagerLink->getService();
    }
}
