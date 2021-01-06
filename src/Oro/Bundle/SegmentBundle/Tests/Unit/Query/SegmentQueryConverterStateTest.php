<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterState;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SegmentQueryConverterStateTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var AbstractQueryDesigner|\PHPUnit\Framework\MockObject\MockObject */
    private $segment;

    /** @var string */
    private $segmentHash;

    /** @var SegmentQueryConverterState */
    private $state;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);

        $this->state = new SegmentQueryConverterState($this->cache);

        $this->segment = $this->createMock(AbstractQueryDesigner::class);
        $this->segment->expects(self::any())
            ->method('getEntity')
            ->willReturn('Test\Entity');
        $this->segment->expects(self::any())
            ->method('getDefinition')
            ->willReturn('test definition');
        $this->segmentHash = md5($this->segment->getEntity() . '::' . $this->segment->getDefinition());
    }

    public function testRegisterQuery()
    {
        $segmentId = 123;
        $segment1Id = 234;
        $segment2Id = 456;

        $this->state->registerQuery($segmentId);
        self::assertEquals($this->segmentHash . '_1', $this->state->buildQueryAlias($segmentId, $this->segment));

        $this->state->registerQuery($segmentId);
        self::assertEquals($this->segmentHash . '_2', $this->state->buildQueryAlias($segmentId, $this->segment));

        $this->state->registerQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segment1Id, $this->segment));
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segmentId, $this->segment));

        $this->state->registerQuery($segment2Id);
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segment2Id, $this->segment));
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segment1Id, $this->segment));
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segmentId, $this->segment));
    }

    public function testUnregisterQuery()
    {
        $segmentId = 123;
        $segment1Id = 234;

        $this->state->registerQuery($segmentId);
        $this->state->registerQuery($segment1Id);
        $this->state->registerQuery($segment1Id);

        $this->state->unregisterQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segment1Id, $this->segment));
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segmentId, $this->segment));

        $this->state->unregisterQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segmentId, $this->segment));

        $this->state->unregisterQuery($segmentId);
    }

    public function testUnregisterQueryWhenNoRegisteredQueries()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Cannot unregister a query for the segment 123 because it was not registered yet.'
        );

        $this->state->unregisterQuery(123);
    }

    public function testUnregisterQueryWhenAllQueriesForSegmentAlreadyUnregistered()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Cannot unregister a query for the segment 123 because it was not registered yet.'
        );

        $this->state->registerQuery(123);
        $this->state->unregisterQuery(123);

        $this->state->unregisterQuery(123);
    }

    public function testIsRootQueryWhenNoRegisteredQueries()
    {
        self::assertFalse($this->state->isRootQuery(123));
    }

    public function testIsRootQueryForNotRegisteredQuery()
    {
        $this->state->registerQuery(123);
        self::assertFalse($this->state->isRootQuery(234));
    }

    public function testIsRootQueryWhenAllQueriesAreUnregistered()
    {
        $segmentId = 123;

        $this->state->registerQuery($segmentId);
        $this->state->unregisterQuery($segmentId);

        self::assertFalse($this->state->isRootQuery($segmentId));
    }

    public function testIsRootQueryWhenAllQueriesAreUnregisteredAndThenNewQueryIsRegistered()
    {
        $segmentId = 123;

        $this->state->registerQuery($segmentId);
        $this->state->unregisterQuery($segmentId);

        $this->state->registerQuery($segmentId);
        self::assertTrue($this->state->isRootQuery($segmentId));
    }

    public function testIsRootQuery()
    {
        $segmentId = 123;
        $segment1Id = 234;

        $this->state->registerQuery($segmentId);
        self::assertTrue($this->state->isRootQuery($segmentId));

        $this->state->registerQuery($segment1Id);
        self::assertFalse($this->state->isRootQuery($segmentId));
        self::assertFalse($this->state->isRootQuery($segment1Id));

        $this->state->registerQuery($segment1Id);
        self::assertFalse($this->state->isRootQuery($segmentId));
        self::assertFalse($this->state->isRootQuery($segment1Id));

        $this->state->unregisterQuery($segment1Id);
        self::assertFalse($this->state->isRootQuery($segmentId));
        self::assertFalse($this->state->isRootQuery($segment1Id));

        $this->state->unregisterQuery($segment1Id);
        self::assertFalse($this->state->isRootQuery($segmentId));
        self::assertFalse($this->state->isRootQuery($segment1Id));

        $this->state->registerQuery($segment1Id);
        self::assertFalse($this->state->isRootQuery($segmentId));
        self::assertFalse($this->state->isRootQuery($segment1Id));

        $this->state->unregisterQuery($segment1Id);
        self::assertFalse($this->state->isRootQuery($segmentId));
        self::assertFalse($this->state->isRootQuery($segment1Id));

        $this->state->unregisterQuery($segmentId);
        self::assertFalse($this->state->isRootQuery($segmentId));
        self::assertFalse($this->state->isRootQuery($segment1Id));
    }

    public function testBuildQueryAliasWhenNoRegisteredQueries()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A query for the segment 123 was not registered yet.');

        $this->state->buildQueryAlias(123, $this->segment);
    }

    public function testBuildQueryAliasForNotRegisteredQuery()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A query for the segment 234 was not registered yet.');

        $this->state->registerQuery(123);
        $this->state->buildQueryAlias(234, $this->segment);
    }

    public function testBuildQueryAliasWhenAllQueriesAreUnregistered()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A query for the segment 123 was not registered yet.');

        $segmentId = 123;

        $this->state->registerQuery($segmentId);
        $this->state->unregisterQuery($segmentId);

        $this->state->buildQueryAlias($segmentId, $this->segment);
    }

    public function testBuildQueryAliasWhenAllQueriesAreUnregisteredAndThenNewQueryIsRegistered()
    {
        $segmentId = 123;

        $this->state->registerQuery($segmentId);
        $this->state->unregisterQuery($segmentId);

        $this->state->registerQuery($segmentId);
        self::assertEquals($this->segmentHash . '_1', $this->state->buildQueryAlias($segmentId, $this->segment));
    }

    public function testBuildQueryAlias()
    {
        $segmentId = 123;
        $segment1Id = 234;

        $this->state->registerQuery($segmentId);
        self::assertEquals($this->segmentHash . '_1', $this->state->buildQueryAlias($segmentId, $this->segment));

        $this->state->registerQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_2', $this->state->buildQueryAlias($segmentId, $this->segment));
        self::assertEquals($this->segmentHash . '_2', $this->state->buildQueryAlias($segment1Id, $this->segment));

        $this->state->registerQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segmentId, $this->segment));
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segment1Id, $this->segment));

        $this->state->unregisterQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segmentId, $this->segment));
        self::assertEquals($this->segmentHash . '_3', $this->state->buildQueryAlias($segment1Id, $this->segment));

        $this->state->registerQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segmentId, $this->segment));
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segment1Id, $this->segment));

        $this->state->unregisterQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segmentId, $this->segment));
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segment1Id, $this->segment));

        $this->state->unregisterQuery($segment1Id);
        self::assertEquals($this->segmentHash . '_4', $this->state->buildQueryAlias($segmentId, $this->segment));

        $this->state->unregisterQuery($segmentId);
    }

    public function testGetQueryFromCacheWhenNoCachedQuery()
    {
        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('segment_query_123')
            ->willReturn(false);

        self::assertNull($this->state->getQueryFromCache(123));
    }

    public function testGetQueryFromCacheWhenHasCachedQuery()
    {
        $cachedQuery = (new QueryBuilder($this->createMock(EntityManagerInterface::class)))
            ->from('Test\Entity', 'e')
            ->select('e');

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('segment_query_123')
            ->willReturn($cachedQuery);

        $query = $this->state->getQueryFromCache(123);
        self::assertEquals($cachedQuery, $query);
        self::assertNotSame($cachedQuery, $query);
    }

    public function testSaveQueryToCache()
    {
        $query = (new QueryBuilder($this->createMock(EntityManagerInterface::class)))
            ->from('Test\Entity', 'e')
            ->select('e');

        $this->cache->expects(self::once())
            ->method('save')
            ->with('segment_query_123', $query)
            ->willReturnCallback(function ($id, $data) use ($query) {
                self::assertNotSame($query, $data);
            });

        $this->state->saveQueryToCache(123, $query);
    }
}
