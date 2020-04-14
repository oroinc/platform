<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\FilterCollection;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\EventListener\DraftableFilterListener;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\StubController;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class DraftableFilterListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var DraftableFilterListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new DraftableFilterListener($this->doctrineHelper);
    }

    public function testOnKernelControllerWithoutId(): void
    {
        $request = Request::create('/entity/draftable/index', 'GET', []);

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManagerForClass');

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerByEntityId(): void
    {
        $entityId = 1;
        $request = Request::create(
            '/entity/draftable/view/' . $entityId,
            'GET',
            ['entityId' => ['id' => $entityId ], 'entityClass' => DraftableEntityStub::class]
        );

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $filters = $this->getFilters();
        $this->mockEntityManager($entityId, $filters, new DraftableEntityStub());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerByEntityIdNotExistentEntity(): void
    {
        $entityId = 1;
        $request = Request::create(
            '/entity/draftable/view/' . $entityId,
            'GET',
            ['entityId' => ['id' => $entityId ], 'entityClass' => DraftableEntityStub::class]
        );

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects($this->never())
            ->method('enable');
        $this->mockEntityManager($entityId, $filters);

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerById(): void
    {
        $entityId = 1;
        $request = Request::create('/entity/draftable/view/' . $entityId, 'GET', ['id' => $entityId]);

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->any())
            ->method('getController')
            ->willReturn([new StubController(), 'viewAction']);

        $filters = $this->getFilters();
        $this->mockEntityManager($entityId, $filters, new DraftableEntityStub());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerByIdNotExistentEntity(): void
    {
        $entityId = 1;
        $request = Request::create('/entity/draftable/view/' . $entityId, 'GET', ['id' => $entityId]);

        /** @var FilterControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FilterControllerEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->any())
            ->method('getController')
            ->willReturn([new StubController(), 'viewAction']);

        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects($this->never())
            ->method('enable');
        $this->mockEntityManager($entityId, $filters);

        $this->listener->onKernelController($event);
    }

    /**
     * @return FilterCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFilters(): FilterCollection
    {
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects($this->once())
            ->method('enable')
            ->with(DraftableFilter::FILTER_ID);

        return $filters;
    }

    /**
     * @param int $id
     * @param FilterCollection|\PHPUnit\Framework\MockObject\MockObject $filters
     * @param DraftableInterface|null $expectedEntity
     */
    private function mockEntityManager(
        int $id,
        FilterCollection $filters,
        DraftableInterface $expectedEntity = null
    ): void {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedEntity);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getFilters')
            ->willReturn($filters);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(DraftableEntityStub::class)
            ->willReturn($em);
    }
}
