<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Provider;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentDeltaData;
use Oro\Bundle\SegmentBundle\Provider\SegmentSnapshotDeltaProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SegmentSnapshotDeltaProviderTest extends WebTestCase
{
    /**
     * @var SegmentSnapshotDeltaProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadSegmentDeltaData::class]);

        $this->provider = new SegmentSnapshotDeltaProvider(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->get('oro_segment.query.dynamic_segment.query_builder')
        );
    }

    public function testGetAddedEntityIds()
    {
        $segment = $this->getReference(LoadSegmentDeltaData::SEGMENT);
        $expectedEntityIds = [
            ['id' => $this->getReference(LoadSegmentDeltaData::SEGMENT_ADDED)->getId()],
        ];

        $addedEntityIds = $this->provider->getAddedEntityIds($segment);
        $this->assertEquals($expectedEntityIds, $addedEntityIds->current());
    }

    public function testGetAddedEntityIdsSegmentWithoutId()
    {
        $segment = new Segment();
        $segment->setEntity(Segment::class);
        $segment->setDefinition(json_encode(LoadSegmentDeltaData::SEGMENT_DEFINITION));
        $expectedEntityIds = [
            ['id' => $this->getReference(LoadSegmentDeltaData::SEGMENT_EXISTING)->getId()],
            ['id' => $this->getReference(LoadSegmentDeltaData::SEGMENT_ADDED)->getId()]
        ];

        $actualEntityIds = $this->provider->getAddedEntityIds($segment)->current();

        $expectedEntityIds = sort($expectedEntityIds);
        $actualEntityIds = sort($actualEntityIds);
        $this->assertEquals($expectedEntityIds, $actualEntityIds);
    }

    public function testGetRemovedEntityIds()
    {
        $segment = $this->getReference(LoadSegmentDeltaData::SEGMENT);
        $expectedEntityIds = [
            ['integerEntityId' => $this->getReference(LoadSegmentDeltaData::SEGMENT_REMOVED)->getId()],
        ];

        $removedEntityIds = $this->provider->getRemovedEntityIds($segment);
        $this->assertEquals($expectedEntityIds, $removedEntityIds->current());
    }

    public function testGetRemovedEntityIdsSegmentWithoutId()
    {
        $segment = new Segment();
        $segment->setEntity(Segment::class);
        $segment->setDefinition(json_encode(LoadSegmentDeltaData::SEGMENT_DEFINITION));

        $actualEntityIds = $this->provider->getRemovedEntityIds($segment);

        $this->assertEmpty($actualEntityIds);
    }
}
