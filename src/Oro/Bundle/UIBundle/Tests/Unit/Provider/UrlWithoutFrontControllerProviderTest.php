<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UrlWithoutFrontControllerProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class UrlWithoutFrontControllerProviderTest extends TestCase
{
    private RouterInterface&MockObject $router;
    private UrlWithoutFrontControllerProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->provider = new UrlWithoutFrontControllerProvider($this->router);
    }

    public function testGenerate(): void
    {
        $name = 'some_route_name';
        $parameters = ['any_route_parameter'];
        $path = 'some/test/path.png';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($name, $parameters)
            ->willReturn($path);
        $this->router->expects($this->any())
            ->method('getContext')
            ->willReturn(new RequestContext('/index.php'));

        self::assertEquals($path, $this->provider->generate($name, $parameters));
    }
}
