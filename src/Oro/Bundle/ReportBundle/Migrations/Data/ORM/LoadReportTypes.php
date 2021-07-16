<?php

namespace Oro\Bundle\ReportBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ReportBundle\Entity\ReportType;

class LoadReportTypes extends AbstractFixture
{
    /**
     * Load available report types
     */
    public function load(ObjectManager $manager)
    {
        $tableReport = new ReportType(ReportType::TYPE_TABLE);
        $tableReport->setLabel('oro.report.type.table');

        $manager->persist($tableReport);
        $manager->flush();
    }
}
