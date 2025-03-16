<?php

namespace Oro\Bundle\EmailBundle\Async\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationsTopic;
use Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Manages {@see Email} associations.
 */
class AssociationManager
{
    private const int EMAIL_BUFFER_SIZE = 100;
    private const int OWNER_IDS_BUFFER_SIZE = 500;

    private bool $queued = true;

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private ActivityManager $activityManager,
        private EmailOwnersProvider $emailOwnersProvider,
        private EmailManager $emailManager,
        private MessageProducerInterface $producer
    ) {
    }

    public function setQueued(bool $queued): void
    {
        $this->queued = $queued;
    }

    /**
     * Adds an association to emails.
     */
    public function processAddAssociation(array $ids, string $targetClass, mixed $targetId): int
    {
        $target = $this->doctrineHelper->getEntityRepository($targetClass)->find($targetId);
        $countNewAssociations = 0;
        $emails = $this->emailManager->findEmailsByIds($ids);
        foreach ($emails as $email) {
            $result = $this->activityManager->addActivityTarget($email, $target);
            if ($result) {
                $countNewAssociations++;
            }
        }

        $this->getEmailEntityManager()->flush();

        return $countNewAssociations;
    }

    /**
     * Makes sure that all email owners have assigned their emails.
     */
    public function processUpdateAllEmailOwners(): void
    {
        $emailOwnerClassNames = $this->emailOwnersProvider->getSupportedEmailOwnerClassNames();
        foreach ($emailOwnerClassNames as $emailOwnerClassName) {
            $ownerColumnName = $this->emailOwnersProvider->getOwnerColumnName($emailOwnerClassName);
            if (!$ownerColumnName) {
                continue;
            }

            $ownerIdsQb = $this->doctrineHelper->getEntityRepository(Email::class)->getOwnerIdsWithEmailsQb(
                $emailOwnerClassName,
                $this->doctrineHelper->getSingleEntityIdentifierFieldName($emailOwnerClassName),
                $ownerColumnName
            );

            $ownerIds = new BufferedIdentityQueryResultIterator($ownerIdsQb);
            $ownerIds->setBufferSize(self::OWNER_IDS_BUFFER_SIZE);
            $ownerIds->setPageLoadedCallback(function (array $rows) use ($emailOwnerClassName) {
                $ownerIds = array_map('current', $rows);
                if ($this->queued) {
                    $this->producer->send(
                        UpdateEmailOwnerAssociationsTopic::getName(),
                        [
                            'ownerClass' => $emailOwnerClassName,
                            'ownerIds' => $ownerIds,
                        ]
                    );
                } else {
                    $this->processUpdateEmailOwner($emailOwnerClassName, $ownerIds);
                }
            });

            // iterate through ownerIds to call pageLoadedCallback
            foreach ($ownerIds as $ownerId) {
            }
        }
    }

    /**
     * Updates email owner association.
     */
    public function processUpdateEmailOwner(string $ownerClassName, array $ownerIds): int
    {
        $ownerQb = $this->createOwnerQb($ownerClassName, $ownerIds);
        $owners = $this->getOwnerIterator($ownerQb);
        $countNewMessages = 0;
        foreach ($owners as $owner) {
            $emailsQB = $this->emailOwnersProvider->getQBEmailsByOwnerEntity($owner);

            /** @var QueryBuilder $emailQB */
            foreach ($emailsQB as $emailQB) {
                $emailIds = [];
                $emails = new BufferedIdentityQueryResultIterator($emailQB);
                $emails->setBufferSize(self::EMAIL_BUFFER_SIZE);
                $emails->setPageCallback(function () use ($owner, &$emailIds, $ownerClassName, &$countNewMessages) {
                    $this->clear();

                    if ($this->queued) {
                        $this->producer->send(
                            AddEmailAssociationsTopic::getName(),
                            [
                                'emailIds' => $emailIds,
                                'targetClass' => $ownerClassName,
                                'targetId' => $owner->getId(),
                            ]
                        );
                    } else {
                        $this->processAddAssociation($emailIds, $ownerClassName, $owner->getId());
                    }

                    $emailIds = [];
                    $countNewMessages++;
                });

                foreach ($emails as $email) {
                    $emailIds[] = $email->getId();
                }
            }
        }

        return $countNewMessages;
    }

    /**
     * Clear UnitOfWork cache
     */
    private function clear(): void
    {
        $clearClass = [
            Email::class,
            EmailBody::class,
            ActivityList::class,
            EmailThread::class
        ];
        foreach ($clearClass as $item) {
            $this->getEmailEntityManager()->clear($item);
        }
    }

    private function createOwnerQb(string $class, array $ids): QueryBuilder
    {
        $qb = $this->doctrineHelper->getEntityRepositoryForClass($class)
            ->createQueryBuilder('o');

        return $qb
            ->andWhere($qb->expr()->in(
                \sprintf('o.%s', $this->doctrineHelper->getSingleEntityIdentifierFieldName($class)),
                ':ids'
            ))
            ->setParameter('ids', $ids);
    }

    private function getOwnerIterator(QueryBuilder $ownerQb): \Iterator
    {
        $iterator = new BufferedIdentityQueryResultIterator($ownerQb);

        $iterator->setBufferSize(1);
        $iterator->setPageCallback(function () {
            $this->getEmailEntityManager()->flush();
            $this->getEmailEntityManager()->clear();
        });

        return $iterator;
    }

    private function getEmailEntityManager(): EntityManagerInterface
    {
        return $this->doctrineHelper->getEntityManagerForClass(Email::class);
    }
}
