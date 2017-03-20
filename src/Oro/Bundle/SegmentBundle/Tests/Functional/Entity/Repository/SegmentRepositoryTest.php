<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SegmentRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadSegmentData::class]);
    }

    public function testFindByEntity()
    {
        /** @var Registry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var SegmentRepository $segmentRepository */
        $segmentRepository = $registry->getRepository('OroSegmentBundle:Segment');

        $result = $segmentRepository->findByEntity('Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity');

        /** @var Segment $dynamicSegment */
        $dynamicSegment = $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC);
        /** @var Segment $staticSegment */
        $staticSegment = $this->getReference(LoadSegmentData::SEGMENT_STATIC);
        $this->assertEquals([
            $dynamicSegment->getId() => $dynamicSegment->getName(),
            $staticSegment->getId() => $staticSegment->getName(),
        ], $result);
    }
}
