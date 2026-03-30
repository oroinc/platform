<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\EventListener;

use Oro\Component\DraftSession\EventListener\EnableEntityDraftsOnRequestListener;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EnableEntityDraftsOnRequestListenerTest extends TestCase
{
    private DraftSessionOrmFilterManager&MockObject $draftSessionOrmFilterManager;
    private EnableEntityDraftsOnRequestListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftSessionOrmFilterManager = $this->createMock(DraftSessionOrmFilterManager::class);

        $this->listener = new EnableEntityDraftsOnRequestListener(
            $this->draftSessionOrmFilterManager,
            ['oro_order_create']
        );
    }

    public function testOnKernelRequestForSubRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_order_create');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('disable');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestForNonApplicableRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'some_other_route');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('disable');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestForApplicableRouteDisablesFilter(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_order_create');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelTerminateForNonApplicableRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'some_other_route');
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $this->createMock(Response::class)
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('enable');

        $this->listener->onKernelTerminate($event);
    }

    public function testOnKernelTerminateForApplicableRouteEnablesFilter(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_order_create');
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $this->createMock(Response::class)
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('enable');

        $this->listener->onKernelTerminate($event);
    }

    public function testOnKernelRequestWithEmptyApplicableRoutes(): void
    {
        $listener = new EnableEntityDraftsOnRequestListener($this->draftSessionOrmFilterManager, []);

        $request = new Request();
        $request->attributes->set('_route', 'oro_order_create');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('disable');

        $listener->onKernelRequest($event);
    }

    public function testOnKernelTerminateWithEmptyApplicableRoutes(): void
    {
        $listener = new EnableEntityDraftsOnRequestListener($this->draftSessionOrmFilterManager, []);

        $request = new Request();
        $request->attributes->set('_route', 'oro_order_create');
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $this->createMock(Response::class)
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('enable');

        $listener->onKernelTerminate($event);
    }

    public function testOnKernelRequestWithMultipleApplicableRoutes(): void
    {
        $listener = new EnableEntityDraftsOnRequestListener(
            $this->draftSessionOrmFilterManager,
            ['oro_order_create', 'oro_order_update', 'oro_order_view']
        );

        $request = new Request();
        $request->attributes->set('_route', 'oro_order_update');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $listener->onKernelRequest($event);
    }

    public function testOnKernelTerminateWithMultipleApplicableRoutes(): void
    {
        $listener = new EnableEntityDraftsOnRequestListener(
            $this->draftSessionOrmFilterManager,
            ['oro_order_create', 'oro_order_update', 'oro_order_view']
        );

        $request = new Request();
        $request->attributes->set('_route', 'oro_order_view');
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $this->createMock(Response::class)
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('enable');

        $listener->onKernelTerminate($event);
    }

    public function testOnKernelRequestWhenRouteIsNull(): void
    {
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('disable');

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelTerminateWhenRouteIsNull(): void
    {
        $request = new Request();
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $this->createMock(Response::class)
        );

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('enable');

        $this->listener->onKernelTerminate($event);
    }
}
