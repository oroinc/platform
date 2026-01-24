<?php

namespace Oro\Bundle\ReportBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ReportBundle\Entity\ReportType;

/**
 * Loads default report types into the database.
 *
 * This data fixture initializes the available report types that can be used in the application.
 * It creates and persists the table report type, which is the primary report type used for
 * displaying data in tabular format.
 */
class LoadReportTypes extends AbstractFixture
{
    /**
     * Load available report types
     */
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $tableReport = new ReportType(ReportType::TYPE_TABLE);
        $tableReport->setLabel('oro.report.type.table');

        $manager->persist($tableReport);
        $manager->flush();
    }
}
