<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\LayoutBundle\EventListener\ThemeListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ThemeListenerTest extends \PHPUnit\Framework\TestCase
{
    private ThemeListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ThemeListener('defaultTheme');
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [KernelEvents::REQUEST => ['onKernelRequest', -20]],
            ThemeListener::getSubscribedEvents()
        );
    }

    public function testShouldSetDefaultTheme(): void
    {
        $masterRequestEvent = $this->createMasterRequestEvent([], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);
        self::assertEquals('defaultTheme', $masterRequestEvent->getRequest()->attributes->get('_theme'));

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        self::assertEquals('defaultTheme', $subRequestEvent->getRequest()->attributes->get('_theme'));
    }

    public function testShouldNotSetSubRequestThemeFromMasterRequestQueryStringWithDebugFalse(): void
    {
        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);
        self::assertEquals('defaultTheme', $masterRequestEvent->getRequest()->attributes->get('_theme'));

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        self::assertEquals('defaultTheme', $subRequestEvent->getRequest()->attributes->get('_theme'));
    }

    public function testShouldSetSubRequestThemeFromMasterRequestQueryStringWithDebugTrue(): void
    {
        $this->listener->setDebug(true);

        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);
        self::assertEquals('testTheme', $masterRequestEvent->getRequest()->attributes->get('_theme'));

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        self::assertEquals('testTheme', $subRequestEvent->getRequest()->attributes->get('_theme'));
    }

    public function testShouldSetSubRequestRouteFromMasterRequest(): void
    {
        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        self::assertEquals('testRoute', $subRequestEvent->getRequest()->attributes->get('_master_request_route'));
    }

    public function testSubRequestRouteShouldNotBeOverridden(): void
    {
        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);

        $subRequestEvent = $this->createSubRequestEvent(['_master_request_route' => 'oldRoute']);
        $this->listener->onKernelRequest($subRequestEvent);
        self::assertEquals('oldRoute', $subRequestEvent->getRequest()->attributes->get('_master_request_route'));
    }

    private function createMasterRequestEvent(array $query = [], array $attributes = []): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request($query, [], $attributes),
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    private function createSubRequestEvent(array $attributes = []): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request([], [], $attributes),
            HttpKernelInterface::SUB_REQUEST
        );
    }
}
