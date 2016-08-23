<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\UIBundle\Layout\Extension\IsMobileContextConfigurator;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Bundle\UIBundle\Provider\UserAgent;

use Oro\Component\Layout\ContextInterface;

class IsMobileContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IsMobileContextConfigurator
     */
    protected $isMobileContextConfigurator;

    /**
     * @var UserAgentProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userAgentProvider;

    /**
     * @var UserAgent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userAgent;

    public function setUp()
    {
        $this->userAgentProvider = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgentProvider')
            ->disableOriginalConstructor()->getMock();

        $this->userAgent = $this->getMockBuilder('Oro\Bundle\UIBundle\Provider\UserAgent')
            ->disableOriginalConstructor()->getMock();

        $this->isMobileContextConfigurator = new IsMobileContextConfigurator($this->userAgentProvider);
    }

    public function testConfigureContext()
    {
        /** @var OptionsResolverInterface|\PHPUnit_Framework_MockObject_MockObject $optionResolver */
        $optionResolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $optionResolver->expects($this->once())
            ->method('setRequired')
            ->with(['is_mobile'])
            ->willReturn($optionResolver);

        $optionResolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with(['is_mobile' => ['boolean']])
            ->willReturn($optionResolver);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $context->expects($this->once())
            ->method('getResolver')
            ->willReturn($optionResolver);

        $isMobile = true;

        $this->userAgent->expects($this->once())
            ->method('isMobile')
            ->willReturn($isMobile);

        $this->userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->willReturn($this->userAgent);

        $context->expects($this->once())
            ->method('set')
            ->with('is_mobile', $isMobile);

        $this->isMobileContextConfigurator->configureContext($context);
    }
}
