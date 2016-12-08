<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\Fixtures\Filters\FixtureInterface;
use Oro\Bundle\SegmentBundle\Tests\Functional\Fixtures\Filters\ToManyToManyContainsAndContains;
use Oro\Bundle\SegmentBundle\Tests\Functional\Fixtures\Filters\ToManyToManyContainsAndNotAnyOf;
use Oro\Bundle\SegmentBundle\Tests\Functional\Fixtures\Filters\ToManyToOneEqualAndEqual;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class FiltersTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider casesProvider
     */
    public function testCases(FixtureInterface $case)
    {
        $em = $this->getEntityManager();
        $case->createData($em);
        $segment = $case->createSegment($em);
        $em->flush();

        $gridName = Segment::GRID_PREFIX . $segment->getId();
        $datagrid = $this->getDatagridManager()->getDatagrid($gridName);
        $actualData = $datagrid->getData()->getData();

        $case->assert($this, $actualData);
    }

    public function casesProvider()
    {
        return [
            [new ToManyToManyContainsAndContains()],
            [new ToManyToManyContainsAndNotAnyOf()],
            [new ToManyToOneEqualAndEqual()],
        ];
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(Segment::class);
    }

    /**
     * @return Manager
     */
    protected function getDatagridManager()
    {
        return $this->getContainer()->get('oro_datagrid.datagrid.manager');
    }
}
