<?php

namespace Oro\Bundle\ReportBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $reportTypeRepo = $doctrine->getManager()->getRepository(ReportType::class);
        /** @var ReportType $reportType */
        foreach ($reportTypeRepo->findAll() as $reportType) {
            $referenceRepository->set('report_type_' . $reportType->getName(), $reportType);
        }
    }
}
