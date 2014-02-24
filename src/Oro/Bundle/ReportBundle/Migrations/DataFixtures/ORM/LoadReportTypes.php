<?php

namespace Oro\Bundle\ReportBundle\Migrations\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ReportBundle\Entity\ReportType;

class LoadReportTypes extends AbstractFixture
{
    /**
     * Load available report types
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $tableReport = new ReportType('TABLE');
        $tableReport->setLabel('oro.report.type.table');
        $this->addReference('table_report', $tableReport);

        $manager->persist($tableReport);

        $manager->flush();
    }
}
