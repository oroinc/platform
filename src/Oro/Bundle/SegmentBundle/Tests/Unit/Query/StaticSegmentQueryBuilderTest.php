<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class StaticSegmentQueryBuilderTest extends SegmentDefinitionTestCase
{
    public function testBuild()
    {
        $segment = $this->getSegment();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $repo = $this->getMockBuilder('Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository')
            ->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('getIdentifiersSelectQueryBuilder')
            ->with($segment)
            ->will($this->returnValue(new QueryBuilder($em)));

        $em->expects($this->once())->method('getRepository')->with('OroSegmentBundle:SegmentSnapshot')
            ->will($this->returnValue($repo));
        $em->expects($this->any())->method('createQuery')
            ->will($this->returnValue(new Query($em)));

        $staticSegmentQB = new StaticSegmentQueryBuilder($em);
        $staticSegmentQB->build($segment);
    }
}
