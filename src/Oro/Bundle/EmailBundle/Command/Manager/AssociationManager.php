<?php

namespace Oro\Bundle\EmailBundle\Command\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Command\AddAssociationCommand;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;

class AssociationManager
{
    const EMAIL_BUFFER_SIZE = 100;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EmailActivityManager */
    protected $emailActivityManager;

    /** @var EmailOwnersProvider */
    protected $emailOwnersProvider;

    /** @var EmailManager */
    protected $emailManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailActivityManager $emailActivityManager,
        EmailOwnersProvider $emailOwnersProvider,
        EmailManager $emailManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->emailActivityManager = $emailActivityManager;
        $this->emailOwnersProvider = $emailOwnersProvider;
        $this->emailManager = $emailManager;
    }

    /**
     * Process of command oro:email:add-associations
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
     * Process of command oro:email:update-email-owner-associations
     *
     * @param $ownerClassName
     * @param $ownerId
     *
     * @return int
     */
    public function processUpdateEmailOwner($ownerClassName, $ownerId)
    {
        $ownerQb = $this->createOwnerQb($ownerClassName, $ownerId);
        $owners = $this->getOwnerIterator($ownerQb);
        $dependenceJob = null;
        $countNewJob = 0;
        foreach ($owners as $owner) {
            $emailsQB = $this->emailOwnersProvider->getQBEmailsByOwnerEntity($owner);

            /** @var QueryBuilder $emailQB */
            foreach ($emailsQB as $emailQB) {
                $emailId = [];
                $emails = (new BufferedQueryResultIterator($emailQB))
                    ->setBufferSize(self::EMAIL_BUFFER_SIZE)
                    ->setPageCallback(function () use (
                        &$owner,
                        &$emailId,
                        &$dependenceJob,
                        &$ownerClassName,
                        &$countNewJob
                    ) {
                        $this->clear();
                        $job = $this->createJob($emailId, $ownerClassName, $owner->getId(), $dependenceJob);

                        $emailId = [];
                        $dependenceJob = $job;
                        $countNewJob++;
                    });

                foreach ($emails as $email) {
                    $emailId[] = $email->getId();
                }
            }
        }

        return $countNewJob;
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
     * @param $emailId
     * @param $ownerClassName
     * @param $ownerId
     * @param $dependenceJob
     *
     * @return Job
     */
    protected function createJob($emailId, $ownerClassName, $ownerId, $dependenceJob)
    {
        foreach ($emailId as $id) {
            $arguments[] = '--id='.$id;
        }
        $arguments[] = '--targetClass=' . $ownerClassName;
        $arguments[] = '--targetId=' . $ownerId;

        $job = new Job(AddAssociationCommand::COMMAND_NAME, $arguments);
        if ($dependenceJob) {
            $job->addDependency($dependenceJob);
        }

        $jobEntityManager = $this->doctrineHelper->getEntityManager($job);
        $jobEntityManager->persist($job);
        $jobEntityManager->flush();

        return $job;
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
     * @return $this
     */
    protected function getOwnerIterator($ownerQb)
    {
        return (new BufferedQueryResultIterator($ownerQb))
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
