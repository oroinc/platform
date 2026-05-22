<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\NoopEventListener;
use Oro\Bundle\EntityBundle\Event\OroEntityListenerResolver;
use Oro\Bundle\EntityBundle\Tests\Unit\Event\Stub\OroEntityListenerResolverTestListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class OroEntityListenerResolverTest extends TestCase
{
    private ContainerInterface&MockObject $container;

    private OroEntityListenerResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->resolver = new OroEntityListenerResolver($this->container);
    }

    public function testDisableAndClearDisabledListenersState(): void
    {
        self::assertFalse($this->resolver->hasDisabledListeners());

        $this->resolver->disableListeners();

        self::assertTrue($this->resolver->hasDisabledListeners());

        $this->resolver->clearDisabledListeners();

        self::assertFalse($this->resolver->hasDisabledListeners());
    }

    public function testResolveReturnsRegisteredObjectWhenListenerIsEnabled(): void
    {
        $listener = new OroEntityListenerResolverTestListener();
        $this->resolver->register($listener);

        self::assertSame($listener, $this->resolver->resolve($listener::class));
    }

    public function testResolveReturnsServiceWhenRegisteredAsServiceAndEnabled(): void
    {
        $listener = new OroEntityListenerResolverTestListener();

        $this->container
            ->expects(self::once())
            ->method('has')
            ->with('test.listener.service')
            ->willReturn(true);

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('test.listener.service')
            ->willReturn($listener);

        $this->resolver->registerService(OroEntityListenerResolverTestListener::class, 'test.listener.service');

        self::assertSame($listener, $this->resolver->resolve(OroEntityListenerResolverTestListener::class));
    }

    public function testResolveReturnsNoopListenerWhenClassIsDisabledByRegexp(): void
    {
        $listener = new OroEntityListenerResolverTestListener();
        $this->resolver->register($listener);

        $this->resolver->disableListeners('^' . preg_quote($listener::class, '~') . '$');

        self::assertInstanceOf(NoopEventListener::class, $this->resolver->resolve($listener::class));
    }

    public function testResolveReturnsRegisteredObjectWhenDisabledRegexpDoesNotMatch(): void
    {
        $listener = new OroEntityListenerResolverTestListener();
        $this->resolver->register($listener);

        $this->resolver->disableListeners('^' . preg_quote('Some\\Other\\Class', '~') . '$');

        self::assertSame($listener, $this->resolver->resolve($listener::class));
    }
}
