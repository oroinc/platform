<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\NavigationBundle\Twig\HashNavExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernel;

class HashNavExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private HashNavExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new HashNavExtension();
    }

    public function testCheckIsHashNavigation(): void
    {
        $event = $this->createMock(RequestEvent::class);

        $event->expects(self::once())
            ->method('getRequestType')
            ->willReturn(HttpKernel::MASTER_REQUEST);

        $request = $this->createMock(Request::class);

        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $request->headers = $this->createMock(HeaderBag::class);

        $request->headers->expects(self::once())
            ->method('get')
            ->willReturn(false);

        $request->expects(self::once())
            ->method('get')
            ->willReturn(true);

        $this->extension->onKernelRequest($event);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'oro_is_hash_navigation', [])
        );
    }

    public function testGetHashNavigationHeaderConst(): void
    {
        self::assertEquals(
            ResponseHashnavListener::HASH_NAVIGATION_HEADER,
            self::callTwigFunction($this->extension, 'oro_hash_navigation_header', [])
        );
    }
}
