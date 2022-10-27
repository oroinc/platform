<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Sends notification to websocket that user have new emails.
 */
class EmailUserListener implements ServiceSubscriberInterface
{
    private const ENTITY_STATUS_NEW = 'new';
    private const ENTITY_STATUS_UPDATE = 'update';

    private ContainerInterface $container;
    private array $processEmailUsersEntities = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->collectNewEmailUserEntities($uow->getScheduledEntityInsertions());
        $this->collectUpdatedEmailUserEntities($uow->getScheduledEntityUpdates(), $uow);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->processEmailUsersEntities) {
            return;
        }

        $processEmailUsersEntities = $this->processEmailUsersEntities;
        $this->processEmailUsersEntities = [];

        $usersWithNewEmails = [];
        /** @var EmailUser $entity */
        foreach ($processEmailUsersEntities as $item) {
            $status = $item['status'];
            $entity = $item['entity'];

            $em = $args->getEntityManager();
            $ownerIds = $this->determineOwners($entity, $em);
            foreach ($ownerIds as $ownerId) {
                if (\array_key_exists($ownerId, $usersWithNewEmails) === true) {
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
            $this->getWebSocketSendProcessor()->send($usersWithNewEmails);
        }
    }

    private function determineOwners(EmailUser $entity, EntityManager $em): array
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

    private function collectNewEmailUserEntities(array $entities): void
    {
        if (!$entities) {
            return;
        }

        foreach ($entities as $entity) {
            if (!$entity instanceof EmailUser || $entity->isSeen()) {
                continue;
            }
            $this->processEmailUsersEntities[] = [
                'status' => self::ENTITY_STATUS_NEW,
                'entity' => $entity
            ];
        }
    }

    private function collectUpdatedEmailUserEntities(array $entities, UnitOfWork $uow): void
    {
        if (!$entities) {
            return;
        }

        foreach ($entities as $entity) {
            if (!$entity instanceof EmailUser) {
                continue;
            }
            $changes = $uow->getEntityChangeSet($entity);
            if (!\array_key_exists('seen', $changes)) {
                continue;
            }
            $this->processEmailUsersEntities[] = [
                'status' => self::ENTITY_STATUS_UPDATE,
                'entity' => $entity
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_email.email_websocket.processor' => WebSocketSendProcessor::class
        ];
    }

    private function getWebSocketSendProcessor(): WebSocketSendProcessor
    {
        return $this->container->get('oro_email.email_websocket.processor');
    }
}
