<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class StaticSegmentQueryBuilderTest extends SegmentDefinitionTestCase
{
    public function testBuild()
    {
        $segment = $this->getSegment();

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects(self::once())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects(self::once())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))
            ->method('getConfiguration')
            ->willReturn($configuration);

        $repo = $this->createMock(SegmentSnapshotRepository::class);
        $repo->expects(self::once())
            ->method('getIdentifiersSelectQueryBuilder')
            ->with($segment)
            ->willReturn(new QueryBuilder($em));

        $em->expects(self::once())
            ->method('getRepository')
            ->with(SegmentSnapshot::class)
            ->willReturn($repo);
        $em->expects(self::any())
            ->method('createQuery')
            ->willReturn(new Query($em));

        $staticSegmentQB = new StaticSegmentQueryBuilder($em);
        $staticSegmentQB->build($segment);
    }
}
