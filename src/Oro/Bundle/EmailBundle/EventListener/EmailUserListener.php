<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailUserListener
{
    const ENTITY_STATUS_NEW = 'new';
    const ENTITY_STATUS_UPDATE = 'update';

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
        foreach ($this->processEmailUsersEntities as $item) {
            $status = $item['status'];
            $entity = $item['entity'];

            $em = $args->getEntityManager();
            $ownerIds = $this->determineOwners($entity, $em);

            foreach ($ownerIds as $ownerId) {
                if (array_key_exists($ownerId, $usersWithNewEmails) === true) {
                    $new = $usersWithNewEmails[$ownerId]['new'];
                    if ($status === self::ENTITY_STATUS_NEW) {
                        $usersWithNewEmails[$ownerId]['new'] = $new + 1;
                    }
                } else {
                    $usersWithNewEmails[$ownerId] = [
                        'entity' => $entity,
                        'new' => $status === self::ENTITY_STATUS_NEW ? 1 : 0,
                    ];
                }
            }
        }

        if ($usersWithNewEmails) {
            $this->processor->send($usersWithNewEmails);
        }
        $this->processEmailUsersEntities = [];
    }

    /**
     * @param EmailUser     $entity
     * @param EntityManager $em
     *
     * @return array
     */
    protected function determineOwners(EmailUser $entity, EntityManager $em)
    {
        $ownerIds = [];
        if ($entity->getOwner() !== null) {
            $ownerIds[] = $entity->getOwner()->getId();
        } else {
            $mailbox = $entity->getMailboxOwner();
            if ($mailbox !== null) {
                $authorizedUsers = $mailbox->getAuthorizedUsers();

                foreach ($authorizedUsers as $user) {
                    $ownerIds[] = $user->getId();
                }

                $authorizedRoles = $mailbox->getAuthorizedRoles();
                foreach ($authorizedRoles as $role) {
                    $users = $em->getRepository('OroUserBundle:Role')
                        ->getUserQueryBuilder($role)
                        ->getQuery()->getResult();

                    foreach ($users as $user) {
                        $ownerIds[] = $user->getId();
                    }
                }
            }
        }

        return array_unique($ownerIds);
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
                    $this->processEmailUsersEntities[] = [
                        'status' => self::ENTITY_STATUS_NEW,
                        'entity' => $entity
                    ];
                }
            }
        }
    }

    /**
     * Collect updated EmailUser entities
     *
     * @param $entities
     * @param $uow - UnitOfWork
     */
    protected function collectUpdatedEmailUserEntities($entities, $uow)
    {
        if ($entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof EmailUser) {
                    $changes = $uow->getEntityChangeSet($entity);
                    if (array_key_exists('seen', $changes) === true) {
                        $this->processEmailUsersEntities[] = [
                            'status' => self::ENTITY_STATUS_UPDATE,
                            'entity' => $entity
                        ];
                    }
                }
            }
        }
    }
}
