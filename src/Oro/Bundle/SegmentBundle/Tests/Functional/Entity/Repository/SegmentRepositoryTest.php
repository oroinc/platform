<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SegmentRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadSegmentData::class]);
    }

    private function getSegmentRepository(): SegmentRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Segment::class);
    }

    private function getAclHelper(): AclHelper
    {
        return self::getContainer()->get('oro_security.acl_helper');
    }

    private function getSegment(string $reference): Segment
    {
        return $this->getReference($reference);
    }

    public function testFindByEntity(): void
    {
        $result = $this->getSegmentRepository()->findByEntity(
            $this->getAclHelper(),
            WorkflowAwareEntity::class
        );

        $dynamicSegment = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC);
        $dynamicSegmentWithFilter = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER);
        $staticSegment = $this->getSegment(LoadSegmentData::SEGMENT_STATIC);
        $staticSegmentWithFilter = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_FILTER_AND_SORTING);
        $staticSegmentWithSegmentFilter = $this->getSegment(LoadSegmentData::SEGMENT_STATIC_WITH_SEGMENT_FILTER);
        $segmentWithFilter1 = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER1);
        $segmentWithFilter2 = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_FILTER2_AND_SEGMENT_FILTER);
        $segmentWithFilter3 = $this->getSegment(LoadSegmentData::SEGMENT_DYNAMIC_WITH_DUPLICATED_SEGMENT_FILTERS);

        self::assertEquals([
            $dynamicSegment->getName() => $dynamicSegment->getId(),
            $dynamicSegmentWithFilter->getName() => $dynamicSegmentWithFilter->getId(),
            $staticSegment->getName() => $staticSegment->getId(),
            $staticSegmentWithFilter->getName() => $staticSegmentWithFilter->getId(),
            $staticSegmentWithSegmentFilter->getName() => $staticSegmentWithSegmentFilter->getId(),
            $segmentWithFilter1->getName() => $segmentWithFilter1->getId(),
            $segmentWithFilter2->getName() => $segmentWithFilter2->getId(),
            $segmentWithFilter3->getName() => $segmentWithFilter3->getId()
        ], $result);
    }
}
