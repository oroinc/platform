<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\EventListener\RequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var RequestListener */
    private $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new RequestListener($this->featureChecker);
    }

    public function testWhenRouteFeatureDisabled(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_login', 'routes')
            ->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('get')
            ->with('_route')
            ->willReturn('oro_login');
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->listener->onRequest($event);
    }

    public function testWhenRouteFeatureEnabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_login', 'routes')
            ->willReturn(true);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('get')
            ->with('_route')
            ->willReturn('oro_login');
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects(self::never())
            ->method('isMasterRequest');

        $this->listener->onRequest($event);
    }

    public function testForNonMasterRequest(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_login', 'routes')
            ->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('get')
            ->with('_route')
            ->willReturn('oro_login');
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $this->listener->onRequest($event);
    }

    public function testNoRoute(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isResourceEnabled');

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('get')
            ->with('_route')
            ->willReturn(null);
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects(self::never())
            ->method('isMasterRequest');
        $event->expects(self::never())
            ->method('setResponse');

        $this->listener->onRequest($event);
    }
}
