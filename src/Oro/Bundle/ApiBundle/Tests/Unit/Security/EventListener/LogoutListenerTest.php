<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Security\EventListener;

use Oro\Bundle\ApiBundle\ApiDoc\RestDocUrlGeneratorInterface;
use Oro\Bundle\ApiBundle\Security\EventListener\LogoutListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;

class LogoutListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var HttpUtils|\PHPUnit\Framework\MockObject\MockObject */
    private $httpUtils;

    /** @var RestDocUrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $restDocUrlGenerator;

    /** @var LogoutListener */
    private $listener;

    protected function setUp(): void
    {
        $this->httpUtils = $this->createMock(HttpUtils::class);
        $this->restDocUrlGenerator = $this->createMock(RestDocUrlGeneratorInterface::class);

        $this->listener = new LogoutListener($this->httpUtils, $this->restDocUrlGenerator);
    }

    public function testOnLogoutWhenResponse(): void
    {
        $request = new Request();
        $response = new Response();
        $event = new LogoutEvent($request, null);
        $event->setResponse($response);

        $this->httpUtils->expects(self::never())
            ->method(self::anything());

        $this->listener->onLogout($event);

        self::assertSame($response, $event->getResponse());
    }

    public function testOnLogoutWhenNoApiView(): void
    {
        $request = new Request();
        $event = new LogoutEvent($request, null);

        $this->httpUtils->expects(self::never())
            ->method(self::anything());

        $this->listener->onLogout($event);

        self::assertNull($event->getResponse());
    }

    public function testOnLogoutWhenApiView(): void
    {
        $request = new Request();
        $apiView = 'sample_api_view';
        $request->query->set('_api_view', 'sample_api_view');
        $event = new LogoutEvent($request, null);

        $uri = '/sample/uri';
        $this->restDocUrlGenerator->expects(self::once())
            ->method('generate')
            ->with($apiView)
            ->willReturn($uri);

        $response = new RedirectResponse($uri, 302);
        $this->httpUtils->expects(self::once())
            ->method('createRedirectResponse')
            ->with($request, $uri)
            ->willReturn($response);

        $this->listener->onLogout($event);

        self::assertEquals(new RedirectResponse($uri, 302), $event->getResponse());
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([LogoutEvent::class => ['onLogout', 128]], LogoutListener::getSubscribedEvents());
    }
}
