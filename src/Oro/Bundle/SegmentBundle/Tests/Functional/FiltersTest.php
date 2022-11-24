<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional;

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
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    /**
     * @dataProvider casesProvider
     */
    public function testCases(FixtureInterface $case)
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Segment::class);
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

    public function casesProvider(): array
    {
        return [
            [new ToManyToManyContainsAndContains()],
            [new ToManyToManyContainsAndNotAnyOf()],
            [new ToManyToOneEqualAndEqual()],
        ];
    }
}
