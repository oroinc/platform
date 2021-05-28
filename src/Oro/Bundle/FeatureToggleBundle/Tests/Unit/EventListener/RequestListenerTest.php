<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\EventListener\RequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListenerTest extends \PHPUnit\Framework\TestCase
{
    private RequestListener $listener;

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    protected function setUp(): void
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)->disableOriginalConstructor()->getMock();
        $this->listener = new RequestListener($this->featureChecker);
    }

    public function testWhenRouteFeatureDisabled(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->featureChecker
            ->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_login', 'routes')
            ->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->method('get')->with('_route')->willReturn('oro_login');
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);
        $event->method('isMasterRequest')->willReturn(true);

        $this->listener->onRequest($event);
    }

    public function testWhenRouteFeatureEnabled(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_login', 'routes')
            ->willReturn(true);

        $request = $this->createMock(Request::class);
        $request->method('get')->with('_route')->willReturn('oro_login');
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);
        $event->method('isMasterRequest')->willReturn(true);

        $this->listener->onRequest($event);
    }

    public function testForNonMasterRequest(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isResourceEnabled')
            ->with('oro_login', 'routes')
            ->willReturn(false);

        $request = $this->createMock(Request::class);
        $request->method('get')->with('_route')->willReturn('oro_login');
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $this->listener->onRequest($event);
    }
}
