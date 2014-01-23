<?php

namespace Oro\Bundle\ReportBundle\DataFixtures\Migrations\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Oro\Bundle\ReportBundle\Entity\ReportType;

class LoadReportTypes extends AbstractFixture implements OrderedFixtureInterface
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

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 40;
    }
}
