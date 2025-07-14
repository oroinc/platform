<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Helper\ApplicationsUrlHelper;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class ApplicationsUrlHelperTest extends TestCase
{
    private RouteProviderInterface&MockObject $routerProvider;
    private RouterInterface&MockObject $router;
    private ApplicationsUrlHelper $instance;

    #[\Override]
    protected function setUp(): void
    {
        $this->routerProvider = $this->createMock(RouteProviderInterface::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->instance = new ApplicationsUrlHelper($this->routerProvider, $this->router);
    }

    public function testGetExecutionUrl(): void
    {
        $parameters = ['param1' => 'val1'];

        $this->routerProvider->expects($this->once())
            ->method('getExecutionRoute')
            ->willReturn('extension_route');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('extension_route', $parameters)
            ->willReturn('ok_extension');

        $this->assertEquals('ok_extension', $this->instance->getExecutionUrl($parameters));
    }

    public function testGetDialogUrl(): void
    {
        $parameters = ['param1' => 'val1'];

        $this->routerProvider->expects($this->once())
            ->method('getFormDialogRoute')
            ->willReturn('dialog_route');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('dialog_route', $parameters)
            ->willReturn('ok_dialog');

        $this->assertEquals('ok_dialog', $this->instance->getDialogUrl($parameters));
    }

    public function testGetPageUrl(): void
    {
        $parameters = ['param1' => 'val1'];

        $this->routerProvider->expects($this->once())
            ->method('getFormPAgeRoute')
            ->willReturn('page_route');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('page_route', $parameters)
            ->willReturn('ok_dialog');

        $this->assertEquals('ok_dialog', $this->instance->getPageUrl($parameters));
    }
}
