<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Layout\Extension\IsLoggedInContextConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IsLoggedInContextConfiguratorTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private IsLoggedInContextConfigurator $contextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->contextConfigurator = new IsLoggedInContextConfigurator($this->tokenAccessor);
    }

    public function testConfigureContextLoggedIn(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context->get('is_logged_in'));
    }

    public function testConfigureContextLoggedOut(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->get('is_logged_in'));
    }
}
