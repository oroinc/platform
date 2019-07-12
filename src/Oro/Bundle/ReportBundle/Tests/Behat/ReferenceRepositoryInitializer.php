<?php

namespace Oro\Bundle\ReportBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        $reportTypeRepo = $doctrine->getManager()->getRepository(ReportType::class);
        /** @var ReportType $reportType */
        foreach ($reportTypeRepo->findAll() as $reportType) {
            $referenceRepository->set('report_type_' . $reportType->getName(), $reportType);
        }
    }
}
