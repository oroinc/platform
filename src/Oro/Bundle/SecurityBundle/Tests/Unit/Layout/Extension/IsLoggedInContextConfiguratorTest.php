<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\SecurityBundle\Layout\Extension\IsLoggedInContextConfigurator;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\Layout\LayoutContext;

class IsLoggedInContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var IsLoggedInContextConfigurator */
    protected $contextConfigurator;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->securityFacade = $this->getMock(SecurityFacade::class, [], [], '', false);
        $this->contextConfigurator = new IsLoggedInContextConfigurator($this->securityFacade);
    }

    public function testConfigureContextLoggedIn()
    {
        $this->securityFacade
            ->expects($this->once())
            ->method('hasLoggedUser')
            ->will($this->returnValue(true));

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertTrue($context->get('is_logged_in'));
    }

    public function testConfigureContextLoggedOut()
    {
        $this->securityFacade
            ->expects($this->once())
            ->method('hasLoggedUser')
            ->will($this->returnValue(false));

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertFalse($context->get('is_logged_in'));
    }
}
