<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

use Oro\Bundle\LayoutBundle\EventListener\ThemeListener;

class ThemeListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeListener */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new ThemeListener('defaultTheme');
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [KernelEvents::REQUEST => ['onKernelRequest', -20]],
            ThemeListener::getSubscribedEvents()
        );
    }

    public function testShouldSetDefaultTheme()
    {
        $masterRequestEvent = $this->createMasterRequestEvent([], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);
        $this->assertEquals('defaultTheme', $masterRequestEvent->getRequest()->attributes->get('_theme'));

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        $this->assertEquals('defaultTheme', $subRequestEvent->getRequest()->attributes->get('_theme'));
    }

    public function testShouldNotSetSubRequestThemeFromMasterRequestQueryStringWithDebugFalse()
    {
        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);
        $this->assertEquals('defaultTheme', $masterRequestEvent->getRequest()->attributes->get('_theme'));

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        $this->assertEquals('defaultTheme', $subRequestEvent->getRequest()->attributes->get('_theme'));
    }

    public function testShouldSetSubRequestThemeFromMasterRequestQueryStringWithDebugTrue()
    {
        $this->listener->setDebug(true);

        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);
        $this->assertEquals('testTheme', $masterRequestEvent->getRequest()->attributes->get('_theme'));

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        $this->assertEquals('testTheme', $subRequestEvent->getRequest()->attributes->get('_theme'));
    }

    public function testShouldSetSubRequestRouteFromMasterRequest()
    {
        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);

        $subRequestEvent = $this->createSubRequestEvent();
        $this->listener->onKernelRequest($subRequestEvent);
        $this->assertEquals('testRoute', $subRequestEvent->getRequest()->attributes->get('_master_request_route'));
    }

    public function testSubRequestRouteShouldNotBeOverridden()
    {
        $masterRequestEvent = $this->createMasterRequestEvent(['_theme' => 'testTheme'], ['_route' => 'testRoute']);
        $this->listener->onKernelRequest($masterRequestEvent);

        $subRequestEvent = $this->createSubRequestEvent(['_master_request_route' => 'oldRoute']);
        $this->listener->onKernelRequest($subRequestEvent);
        $this->assertEquals('oldRoute', $subRequestEvent->getRequest()->attributes->get('_master_request_route'));
    }

    /**
     * @param array $query
     * @param array $attributes
     *
     * @return GetResponseEvent
     */
    protected function createMasterRequestEvent(array $query = [], array $attributes = [])
    {
        return new GetResponseEvent(
            $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            new Request($query, [], $attributes),
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    /**
     * @param array $attributes
     *
     * @return GetResponseEvent
     */
    protected function createSubRequestEvent(array $attributes = [])
    {
        return new GetResponseEvent(
            $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            new Request([], [], $attributes),
            HttpKernelInterface::SUB_REQUEST
        );
    }
}
