<?php

namespace Oro\Bundle\LDAPBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\LDAP\LdapChannelManager;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListener
{

    /** @var ServiceLink */
    private $channelManagerLink;

    /** @var User[] */
    protected $newUsers = [];

    /** @var User[] */
    protected $updatedUsers = [];

    public function __construct(ServiceLink $channelManagerLink)
    {
        $this->channelManagerLink = $channelManagerLink;
    }

    protected function processNew(array &$users, UnitOfWork $uow)
    {
        /** @var LdapChannelManager $manager */
        $manager = $this->channelManagerLink->getService();

        foreach ($users as $user) {
            $manager->exportThroughAllChannels($user);
        }

        $users = [];
    }

    protected function processUpdated(array &$users, UnitOfWork $uow)
    {
        /** @var LdapChannelManager $manager */
        $manager = $this->channelManagerLink->getService();

        foreach ($users as $user) {
            $changedFields = $uow->getEntityChangeSet($user);
            $manager->exportThroughUsersChannels($user, $changedFields);
        }

        $users = [];
    }

    /**
     * Happens after entity gets flushed.
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        $this->processUpdated($this->updatedUsers, $uow);

        $this->processNew($this->newUsers, $uow);
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
            if ($this->isValidUser($entity)) {
                $this->newUsers[] = $entity;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->isValidUser($entity)) {
                $this->updatedUsers[] = $entity;
            }
        }
    }

    protected function isValidUser($entity)
    {
        if ($entity instanceof User) {
            return true;
        }

        return false;
    }
}
