<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\LayoutBundle\Event\LayoutContextChangedEvent;
use Oro\Bundle\LayoutBundle\EventListener\LayoutContextChangedListener;
use Oro\Bundle\LayoutBundle\Layout\TwigEnvironmentAwareLayoutRendererInterface;
use Oro\Component\Layout\LayoutContext;
use Twig\Environment;

class LayoutContextChangedListenerTest extends \PHPUnit\Framework\TestCase
{
    private TwigEnvironmentAwareLayoutRendererInterface|\PHPUnit\Framework\MockObject\MockObject $twigLayoutRenderer;

    private Environment|\PHPUnit\Framework\MockObject\MockObject $environment;

    private LayoutContextChangedListener $listener;

    protected function setUp(): void
    {
        $this->twigLayoutRenderer = $this->createMock(TwigEnvironmentAwareLayoutRendererInterface::class);
        $this->environment = $this->createMock(Environment::class);

        $this->twigLayoutRenderer
            ->expects(self::once())
            ->method('getEnvironment')
            ->willReturn($this->environment);

        $this->listener = new LayoutContextChangedListener($this->twigLayoutRenderer);
    }

    public function testOnContextChangedWhenNoCurrentContext(): void
    {
        $event = new LayoutContextChangedEvent(null, null);

        $this->twigLayoutRenderer
            ->expects(self::once())
            ->method('setEnvironment')
            ->with(
                self::callback(function (Environment $environment) {
                    self::assertSame($this->environment, $environment);

                    return true;
                })
            );

        $this->listener->onContextChanged($event);
    }

    public function testOnContextChangedWhenCurrentContext(): void
    {
        $context = new LayoutContext();
        $context->resolve();
        $event = new LayoutContextChangedEvent(null, $context);

        $this->twigLayoutRenderer
            ->expects(self::once())
            ->method('setEnvironment')
            ->with(
                self::callback(function (Environment $environment) {
                    self::assertEquals($this->environment, $environment);
                    self::assertNotSame($this->environment, $environment);

                    return true;
                })
            );

        $this->listener->onContextChanged($event);
    }
}
