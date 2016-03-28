<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\UIBundle\Layout\Extension\UserAgentContextConfigurator;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Layout\ContextInterface;

class UserAgentContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserAgentContextConfigurator
     */
    protected $userAgentContextConfigurator;

    /**
     * @var UserAgentProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userAgentProvider;

    public function setUp()
    {
        $this->userAgentProvider = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgentProvider')
            ->disableOriginalConstructor()->getMock();

        $this->userAgentContextConfigurator = new UserAgentContextConfigurator($this->userAgentProvider);
    }

    public function testConfigureContext()
    {
        /** @var OptionsResolverInterface|\PHPUnit_Framework_MockObject_MockObject $optionResolver */
        $optionResolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $optionResolver->expects($this->once())
            ->method('setRequired')
            ->with(['user_agent'])
            ->willReturn($optionResolver);

        $optionResolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with(['user_agent' => ['Oro\Bundle\UIBundle\Provider\UserAgentInterface']])
            ->willReturn($optionResolver);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $context->expects($this->once())
            ->method('getResolver')
            ->willReturn($optionResolver);

        $userAgent = 'safari';

        $this->userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->willReturn($userAgent);

        $context->expects($this->once())
            ->method('set')
            ->with('user_agent', $userAgent);

        $this->userAgentContextConfigurator->configureContext($context);
    }
}
