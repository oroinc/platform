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
        $this->initClient([], self::generateBasicAuthHeader());
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

        $response = $this->client->requestGrid(
            ['gridName' => Segment::GRID_PREFIX . $segment->getId()],
            [],
            true
        );
        $result = self::getJsonResponseContent($response, 200);

        $case->assert($this, $result['data']);
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
        return self::getContainer()->get('doctrine')
            ->getManagerForClass(Segment::class);
    }

    /**
     * @return Manager
     */
    protected function getDatagridManager()
    {
        return self::getContainer()->get('oro_datagrid.datagrid.manager');
    }
}
