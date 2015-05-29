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

    /**
     * @param ServiceLink $managerProviderLink Service link with ChannelManagerProvider
     */
    public function __construct(ServiceLink $managerProviderLink)
    {
        $this->managerProviderLink = $managerProviderLink;
    }

    protected function processNew()
    {
        /** @var ChannelManagerProvider $managerProvider */
        $managerProvider = $this->managerProviderLink->getService();

        foreach ($this->newUsers as $user) {
            $managerProvider->save($user);
        }

        $this->newUsers = [];
    }

    /**
     * @param UnitOfWork $uow
     */
    protected function processUpdated(UnitOfWork $uow)
    {
        /** @var ChannelManagerProvider $provider */
        $provider = $this->managerProviderLink->getService();
        $channels = $provider->getChannels();

        foreach ($this->updatedUsers as $user) {
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

        $this->updatedUsers = [];
    }

    /**
     * Happens after entity gets flushed.
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        $this->processUpdated($uow);

        $this->processNew();
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
            if ($entity instanceof User) {
                $this->newUsers[] = $entity;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $this->updatedUsers[] = $entity;
            }
        }
    }
}
