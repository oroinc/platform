<?php

namespace Oro\Bundle\EmailBundle\Async\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AssociationManager
{
    const EMAIL_BUFFER_SIZE = 100;
    const OWNER_IDS_BUFFER_SIZE = 500;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EmailActivityManager */
    protected $emailActivityManager;

    /** @var EmailOwnersProvider */
    protected $emailOwnersProvider;

    /** @var EmailManager */
    protected $emailManager;

    /** @var MessageProducerInterface */
    protected $producer;

    /** @var bool */
    protected $queued = true;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EmailActivityManager $emailActivityManager
     * @param EmailOwnersProvider $emailOwnersProvider
     * @param EmailManager $emailManager
     * @param MessageProducerInterface $producer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailActivityManager $emailActivityManager,
        EmailOwnersProvider $emailOwnersProvider,
        EmailManager $emailManager,
        MessageProducerInterface $producer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->emailActivityManager = $emailActivityManager;
        $this->emailOwnersProvider = $emailOwnersProvider;
        $this->emailManager = $emailManager;
        $this->producer = $producer;
    }

    /**
     * @param bool $queued
     */
    public function setQueued($queued)
    {
        $this->queued = $queued;
    }

    /**
     * Add association to email
     *
     * @param int[] $ids
     * @param string $targetClass
     * @param int $targetId
     *
     * @return int
     */
    public function processAddAssociation($ids, $targetClass, $targetId)
    {
        $target = $this->doctrineHelper->getEntityRepository($targetClass)->find($targetId);
        $countNewAssociations = 0;
        $emails =$this->emailManager->findEmailsByIds($ids);
        foreach ($emails as $email) {
            $result = $this->emailActivityManager->addAssociation($email, $target);

            if ($result) {
                $countNewAssociations++;
            }
        }

        $this->doctrineHelper->getEntityManager('Oro\Bundle\EmailBundle\Entity\Email')->flush();

        return $countNewAssociations;
    }

    /**
     * Makes sure that all email owners have assigned their emails
     */
    public function processUpdateAllEmailOwners()
    {
        $emailOwnerClasseNames = $this->emailOwnersProvider->getSupportedEmailOwnerClassNames();
        foreach ($emailOwnerClasseNames as $emailOwnerClassName) {
            $ownerColumnName = $this->emailOwnersProvider->getOwnerColumnName($emailOwnerClassName);
            if (!$ownerColumnName) {
                continue;
            }

            $ownerIdsQb = $this->doctrineHelper
                ->getEntityRepository(Email::class)
                ->getOwnerIdsWithEmailsQb(
                    $emailOwnerClassName,
                    $this->doctrineHelper->getSingleEntityIdentifierFieldName($emailOwnerClassName),
                    $ownerColumnName
                );

            $ownerIds = (new BufferedIdentityQueryResultIterator($ownerIdsQb))
                ->setBufferSize(self::OWNER_IDS_BUFFER_SIZE)
                ->setPageLoadedCallback(function (array $rows) use ($emailOwnerClassName) {
                    $ownerIds = array_map('current', $rows);
                    if ($this->queued) {
                        $this->producer->send(
                            Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS,
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
     * Update email owner association.
     *
     * @param string $ownerClassName
     * @param array $ownerIds
     *
     * @return int
     */
    public function processUpdateEmailOwner($ownerClassName, array $ownerIds)
    {
        $ownerQb = $this->createOwnerQb($ownerClassName, $ownerIds);
        $owners = $this->getOwnerIterator($ownerQb);
        $countNewMessages = 0;
        foreach ($owners as $owner) {
            $emailsQB = $this->emailOwnersProvider->getQBEmailsByOwnerEntity($owner);

            /** @var QueryBuilder $emailQB */
            foreach ($emailsQB as $emailQB) {
                $emailIds = [];
                $emails = (new BufferedIdentityQueryResultIterator($emailQB))
                    ->setBufferSize(self::EMAIL_BUFFER_SIZE)
                    ->setPageCallback(function () use (
                        &$owner,
                        &$emailIds,
                        &$ownerClassName,
                        &$countNewMessages
                    ) {
                        $this->clear();

                        if ($this->queued) {
                            $this->producer->send(Topics::ADD_ASSOCIATION_TO_EMAILS, [
                                'emailIds' => $emailIds,
                                'targetClass' => $ownerClassName,
                                'targetId' => $owner->getId(),
                            ]);
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
    protected function clear()
    {
        $clearClass = [
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\EmailBundle\Entity\EmailBody',
            'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
            'Oro\Bundle\EmailBundle\Entity\EmailThread'
        ];

        foreach ($clearClass as $item) {
            $this->getEmailEntityManager()->clear($item);
        }
    }

    /**
     * @param string $class
     * @param array $ids
     *
     * @return QueryBuilder
     */
    protected function createOwnerQb($class, array $ids)
    {
        $qb = $this->doctrineHelper->getEntityRepositoryForClass($class)
            ->createQueryBuilder('o');

        return $qb
            ->andWhere($qb->expr()->in(
                sprintf('o.%s', $this->doctrineHelper->getSingleEntityIdentifierFieldName($class)),
                ':ids'
            ))
            ->setParameter('ids', $ids);
    }

    /**
     * @param QueryBuilder $ownerQb
     *
     * @return \Iterator
     */
    protected function getOwnerIterator(QueryBuilder $ownerQb)
    {
        return (new BufferedIdentityQueryResultIterator($ownerQb))
            ->setBufferSize(1)
            ->setPageCallback(function () {
                $this->getEmailEntityManager()->flush();
                $this->getEmailEntityManager()->clear();
            });
    }

    /**
     * @return EntityManager
     */
    protected function getEmailEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass('OroEmailBundle:Email');
    }
}
