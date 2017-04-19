<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentSnapshotData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SegmentProviderTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadSegmentSnapshotData::class,
        ]);
    }

    public function testGetCollectionDynamic()
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        $dataProvider = $this->getContainer()->get('oro_segment.layout.data_provider.segment');
        $this->assertCount(50, $dataProvider->getCollection($segment->getId()));
    }

    public function testGetCollectionStatic()
    {
        /** @var Segment $segment */
        $segment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);
        $dataProvider = $this->getContainer()->get('oro_segment.layout.data_provider.segment');
        $this->assertCount(50, $dataProvider->getCollection($segment->getId()));
    }

    public function testGetCollectionWithoutSegment()
    {
        $dataProvider = $this->getContainer()->get('oro_segment.layout.data_provider.segment');
        $this->assertEquals([], $dataProvider->getCollection(0));
    }
}
