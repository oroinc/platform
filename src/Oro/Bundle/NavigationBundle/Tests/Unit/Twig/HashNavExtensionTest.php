<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\NavigationBundle\Twig\HashNavExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class HashNavExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var HashNavExtension */
    private $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $container = self::getContainerBuilder()
            ->add(RequestStack::class, $this->requestStack)
            ->getContainer($this);

        $this->extension = new HashNavExtension($container);
    }

    public function testCheckIsHashNavigationWhenNoMainRequest(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(null);

        self::assertFalse(self::callTwigFunction($this->extension, 'oro_is_hash_navigation', []));
    }

    public function testCheckIsHashNavigationWhenNoHashNavigationHeader(): void
    {
        $mainRequest = Request::create('http://example.com');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($mainRequest);

        self::assertFalse(self::callTwigFunction($this->extension, 'oro_is_hash_navigation', []));
    }

    public function testCheckIsHashNavigationWhenHashNavigationHeaderExistsInHeaders(): void
    {
        $mainRequest = Request::create('http://example.com');
        $mainRequest->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, true);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($mainRequest);

        self::assertTrue(self::callTwigFunction($this->extension, 'oro_is_hash_navigation', []));
    }

    public function testCheckIsHashNavigationWhenHashNavigationHeaderExistsInQueryString(): void
    {
        $mainRequest = Request::create('http://example.com');
        $mainRequest->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, false);
        $mainRequest->query->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, true);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($mainRequest);

        self::assertTrue(self::callTwigFunction($this->extension, 'oro_is_hash_navigation', []));
    }

    public function testGetHashNavigationHeaderConst(): void
    {
        self::assertEquals(
            ResponseHashnavListener::HASH_NAVIGATION_HEADER,
            self::callTwigFunction($this->extension, 'oro_hash_navigation_header', [])
        );
    }
}
