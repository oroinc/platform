<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Layout\DataProvider\SegmentProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SegmentProviderTest extends TestCase
{
    private SegmentProvider $provider;
    private SegmentManager&MockObject $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(SegmentManager::class);
        $this->provider = new SegmentProvider($this->manager);
    }

    public function testGetCollection(): void
    {
        $segment = new Segment();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn(['result']);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->manager->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->manager->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn($qb);

        $this->assertEquals(['result'], $this->provider->getCollection(1));
    }

    public function testGetCollectionWithoutSegment(): void
    {
        $this->manager->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->manager->expects($this->never())
            ->method('getEntityQueryBuilder');

        $this->assertEquals([], $this->provider->getCollection(1));
    }

    public function testGetCollectionWithoutQueryBuilder(): void
    {
        $segment = new Segment();

        $this->manager->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($segment);

        $this->manager->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn(null);

        $this->assertEquals([], $this->provider->getCollection(1));
    }
}
