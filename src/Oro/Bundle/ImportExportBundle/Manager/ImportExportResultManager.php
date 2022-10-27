<?php

namespace Oro\Bundle\ImportExportBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Responsible for saving import/export results
 */
class ImportExportResultManager
{
    private ManagerRegistry $doctrine;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(ManagerRegistry $doctrine, TokenAccessorInterface $tokenAccessor)
    {
        $this->doctrine = $doctrine;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function saveResult(
        int $jobId,
        string $type,
        string $entity,
        User $owner = null,
        string $fileName = null,
        array $options = []
    ): ImportExportResult {
        $importExportResult = new ImportExportResult();
        $importExportResult
            ->setJobId($jobId)
            ->setEntity($entity)
            ->setFilename($fileName)
            ->setType($type)
            ->setOptions($options);

        $organization = $this->tokenAccessor->getOrganization();
        if ($organization) {
            $importExportResult->setOrganization($organization);
        }

        if ($owner) {
            $importExportResult->setOwner($owner);
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(ImportExportResult::class);
        $em->persist($importExportResult);
        $em->flush();

        return $importExportResult;
    }

    public function markResultsAsExpired(\DateTime $from, \DateTime $to): void
    {
        $em = $this->doctrine->getManagerForClass(ImportExportResult::class);
        /** @var ImportExportResultRepository $importExportResultRepository */
        $importExportResultRepository = $em->getRepository(ImportExportResult::class);
        $importExportResultRepository->updateExpiredRecords($from, $to);

        $em->flush();
    }
}
