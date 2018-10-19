<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Layout\Extension\IsLoggedInContextConfigurator;
use Oro\Component\Layout\LayoutContext;

class IsLoggedInContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var IsLoggedInContextConfigurator */
    protected $contextConfigurator;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->contextConfigurator = new IsLoggedInContextConfigurator($this->tokenAccessor);
    }

    public function testConfigureContextLoggedIn()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->will($this->returnValue(true));

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context->get('is_logged_in'));
    }

    public function testConfigureContextLoggedOut()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->will($this->returnValue(false));

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->get('is_logged_in'));
    }
}
