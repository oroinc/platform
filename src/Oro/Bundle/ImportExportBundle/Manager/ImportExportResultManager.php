<?php

namespace Oro\Bundle\ImportExportBundle\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Responsible for saving import/export results
 */
class ImportExportResultManager
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $manager
     */
    public function __construct(ManagerRegistry $manager)
    {
        $this->registry = $manager;
    }

    /**
     * @param int $jobId
     * @param string|null $type
     * @param User|null $owner
     * @param string|null $fileName
     *
     * @return ImportExportResult
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveResult(
        int $jobId,
        string $type,
        User $owner = null,
        string $fileName = null
    ): ImportExportResult {
        $importExportResult = new ImportExportResult();
        $importExportResult
            ->setJobId($jobId)
            ->setFilename($fileName)
            ->setType($type);

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(ImportExportResult::class);
        $em->persist($importExportResult);
        if ($owner) {
            $importExportResult->setOwner($owner);
            $importExportResult->setOrganization($owner->getOrganization());
        }
        $em->flush();

        return $importExportResult;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     */
    public function markResultsAsExpired(\DateTime $from, \DateTime $to): void
    {
        $em = $this->registry->getManagerForClass(ImportExportResult::class);
        /** @var ImportExportResultRepository $importExportResultRepository */
        $importExportResultRepository = $em->getRepository(ImportExportResult::class);
        $importExportResultRepository->updateExpiredRecords($from, $to);

        $em->flush();
    }
}
