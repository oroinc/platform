<?php

namespace Oro\Bundle\ImportExportBundle\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
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
     * @param string|null $jobCode
     * @param string|null $fileName
     *
     * @return ImportExportResult
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveResult(int $jobId, string $jobCode = null, string $fileName = null): ImportExportResult
    {
        $importExportResult = new ImportExportResult();
        $importExportResult
            ->setJobId($jobId)
            ->setFilename($fileName)
            ->setJobCode($jobCode);

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(ImportExportResult::class);
        $em->persist($importExportResult);
        $em->flush();

        return $importExportResult;
    }
}
