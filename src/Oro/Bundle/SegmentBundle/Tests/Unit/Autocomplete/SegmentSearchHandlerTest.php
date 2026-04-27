<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\SegmentBundle\Autocomplete\SegmentSearchHandler;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SegmentSearchHandlerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private RequestStack&MockObject $requestStack;
    private ObjectRepository&MockObject $repository;
    private QueryBuilder&MockObject $qb;
    private AbstractQuery&MockObject $query;
    private SegmentSearchHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->query = $this->createMock(AbstractQuery::class);

        $this->qb = $this->createMock(QueryBuilder::class);
        $this->qb->method('orderBy')->willReturnSelf();
        $this->qb->method('setFirstResult')->willReturnSelf();
        $this->qb->method('setMaxResults')->willReturnSelf();
        $this->qb->method('where')->willReturnSelf();
        $this->qb->method('andWhere')->willReturnSelf();
        $this->qb->method('setParameter')->willReturnSelf();
        $this->qb->method('getQuery')->willReturn($this->query);

        $this->repository = $this->createMock(EntityRepository::class);
        $this->repository->method('createQueryBuilder')->with('s')->willReturn($this->qb);

        $this->doctrine->method('getRepository')->with(Segment::class)->willReturn($this->repository);

        $this->handler = new SegmentSearchHandler($this->doctrine, $this->requestStack);
    }

    public function testGetEntityName(): void
    {
        self::assertSame(Segment::class, $this->handler->getEntityName());
    }

    public function testGetProperties(): void
    {
        self::assertSame(['id', 'name'], $this->handler->getProperties());
    }

    public function testConvertItem(): void
    {
        $segment = $this->createMock(Segment::class);
        $segment->method('getId')->willReturn(42);
        $segment->method('getName')->willReturn('My Segment');

        self::assertSame(
            ['id' => 42, 'name' => 'My Segment'],
            $this->handler->convertItem($segment)
        );
    }

    public function testSearchByQuery(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $segments = [$this->createSegment(1, 'Foo')];
        $this->query->method('getResult')->willReturn($segments);

        $this->qb->expects(self::once())
            ->method('where')
            ->with('s.name LIKE :query')
            ->willReturnSelf();
        $this->qb->expects(self::once())
            ->method('setParameter')
            ->with('query', '%foo%')
            ->willReturnSelf();
        $this->qb->expects(self::never())->method('andWhere');

        $result = $this->handler->search('foo', 1, 10);

        self::assertSame(
            ['results' => [['id' => 1, 'name' => 'Foo']], 'more' => false],
            $result
        );
    }

    public function testSearchWithEmptyQuery(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->query->method('getResult')->willReturn([]);

        $this->qb->expects(self::never())->method('where');

        $result = $this->handler->search('', 1, 10);

        self::assertSame(['results' => [], 'more' => false], $result);
    }

    public function testSearchWithEntityClassFilter(): void
    {
        $request = $this->createMock(Request::class);
        $request->query = new InputBag(['entity_class' => 'App\Entity\Customer']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->query->method('getResult')->willReturn([]);

        $this->qb->expects(self::once())
            ->method('andWhere')
            ->with('s.entity = :entity')
            ->willReturnSelf();
        $this->qb->expects(self::atLeast(1))
            ->method('setParameter')
            ->willReturnSelf();

        $this->handler->search('foo', 1, 10);
    }

    public function testSearchWithNoEntityClassInRequest(): void
    {
        $request = $this->createMock(Request::class);
        $request->query = new InputBag([]);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->query->method('getResult')->willReturn([]);

        $this->qb->expects(self::never())->method('andWhere');

        $this->handler->search('foo', 1, 10);
    }

    public function testSearchById(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $segments = [$this->createSegment(5, 'Bar')];
        $this->query->method('getResult')->willReturn($segments);

        $this->qb->expects(self::once())
            ->method('where')
            ->with('s.id = :id')
            ->willReturnSelf();
        $this->qb->expects(self::once())
            ->method('setParameter')
            ->with('id', 5)
            ->willReturnSelf();

        $result = $this->handler->search('5', 1, 10, true);

        self::assertSame(
            ['results' => [['id' => 5, 'name' => 'Bar']], 'more' => false],
            $result
        );
    }

    public function testSearchHasMore(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $segments = [
            $this->createSegment(1, 'A'),
            $this->createSegment(2, 'B'),
            $this->createSegment(3, 'C'), // extra item indicating more results
        ];
        $this->query->method('getResult')->willReturn($segments);

        $result = $this->handler->search('', 1, 2);

        self::assertTrue($result['more']);
        self::assertCount(2, $result['results']);
    }

    public function testSearchWithNoRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->query->method('getResult')->willReturn([]);

        $this->qb->expects(self::never())->method('andWhere');

        $this->handler->search('foo', 1, 10);
    }

    private function createSegment(int $id, string $name): Segment&MockObject
    {
        $segment = $this->createMock(Segment::class);
        $segment->method('getId')->willReturn($id);
        $segment->method('getName')->willReturn($name);

        return $segment;
    }
}
