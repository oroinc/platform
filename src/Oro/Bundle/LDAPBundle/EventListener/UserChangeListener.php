<?php

namespace Oro\Bundle\LDAPBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListener
{
    /** @var ServiceLink */
    private $managerProviderLink;

    /** @var User[] */
    protected $newUsers = [];

    /** @var User[] */
    protected $updatedUsers = [];

    public function __construct(ServiceLink $managerProviderLink)
    {
        $this->managerProviderLink = $managerProviderLink;
    }

    protected function processNew(array &$users)
    {
        /** @var ChannelManagerProvider $managerProvider */
        $managerProvider = $this->managerProviderLink->getService();

        foreach ($users as $user) {
            $managerProvider->save($user);
        }

        $users = [];
    }

    protected function processUpdated(array &$users, UnitOfWork $uow)
    {
        /** @var ChannelManagerProvider $provider */
        $provider = $this->managerProviderLink->getService();
        $channels = $provider->getChannels();

        foreach ($users as $user) {
            $mappings = (array) $user->getLdapMappings();

            foreach ($mappings as $channelId => $dn) {
                $changedFields = $uow->getEntityChangeSet($user);
                $channel = $channels[$channelId];
                $mappedFields = $provider->channel($channel)->getSynchronizedFields();
                $common = array_intersect($mappedFields, array_keys($changedFields));

                if (!empty($common)) {
                    $provider->channel($channel)->save($user);
                }
            }
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

        $this->processNew($this->newUsers);
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
