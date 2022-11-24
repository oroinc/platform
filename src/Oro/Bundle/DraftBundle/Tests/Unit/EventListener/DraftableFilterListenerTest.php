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
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            fn ($x) => $x,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->doctrineHelper->expects(self::never())
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

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            fn ($x) => $x,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

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

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            fn ($x) => $x,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects(self::never())
            ->method('enable');
        $this->mockEntityManager($entityId, $filters);

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerById(): void
    {
        $entityId = 1;
        $request = Request::create('/entity/draftable/view/' . $entityId, 'GET', ['id' => $entityId]);

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new StubController(), 'viewAction'],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $filters = $this->getFilters();
        $this->mockEntityManager($entityId, $filters, new DraftableEntityStub());

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerByIdNotExistentEntity(): void
    {
        $entityId = 1;
        $request = Request::create('/entity/draftable/view/' . $entityId, 'GET', ['id' => $entityId]);

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new StubController(), 'viewAction'],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects(self::never())
            ->method('enable');
        $this->mockEntityManager($entityId, $filters);

        $this->listener->onKernelController($event);
    }

    private function getFilters(): FilterCollection|\PHPUnit\Framework\MockObject\MockObject
    {
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects(self::once())
            ->method('enable')
            ->with(DraftableFilter::FILTER_ID);

        return $filters;
    }

    private function mockEntityManager(
        int $id,
        FilterCollection|\PHPUnit\Framework\MockObject\MockObject $filters,
        DraftableInterface $expectedEntity = null
    ): void {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn($expectedEntity);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);
        $em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(DraftableEntityStub::class)
            ->willReturn($em);
    }
}
