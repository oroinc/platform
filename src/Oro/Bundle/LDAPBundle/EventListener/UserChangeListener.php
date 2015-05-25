<?php

namespace Oro\Bundle\LDAPBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListener
{
    /** @var ServiceLink */
    protected $ldapManagerFactoryLink;

    /** @var array */
    protected $synchronizedFields = [];

    /** @var array */
    protected $entitiesToUpdate = [];

    /**
     * @param ServiceLink $ldapManagerFactoryLink
     */
    public function __construct(ServiceLink $ldapManagerFactoryLink)
    {
        $this->ldapManagerFactoryLink = $ldapManagerFactoryLink;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        array_map(function ($entity) {
            $this->getLdapManager($entity->getLdapIntegrationChannel())->save($entity);
        }, $this->entitiesToUpdate);
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

        if (($entity->getDn() === null) || ($entity->getLdapIntegrationChannel() === null)) {
            return;
        }

        $changedFields = array_keys($uow->getEntityChangeSet($entity));
        if (!array_intersect($this->getSynchronizedFields($entity->getLdapIntegrationChannel()), $changedFields)) {
            return;
        }

        $this->entitiesToUpdate[] = $entity;
    }

    /**
     * @param Channel $channel
     * @return array
     */
    protected function getSynchronizedFields(Channel $channel)
    {
        if (!$this->synchronizedFields) {
            $this->synchronizedFields = $this->getLdapManager($channel)->getSynchronizedFields();
        }

        return $this->synchronizedFields;
    }

    /**
     * @param Channel $channel
     * @return LdapManager
     * @throws \Exception
     */
    protected function getLdapManager(Channel $channel)
    {
        return $this->ldapManagerFactoryLink->getService()->getInstanceForChannel($channel);
    }
}
