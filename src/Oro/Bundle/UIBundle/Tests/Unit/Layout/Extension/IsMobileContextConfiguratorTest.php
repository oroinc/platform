<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\UIBundle\Layout\Extension\IsMobileContextConfigurator;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IsMobileContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IsMobileContextConfigurator
     */
    protected $isMobileContextConfigurator;

    /**
     * @var UserAgentProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $userAgentProvider;

    /**
     * @var UserAgent|\PHPUnit\Framework\MockObject\MockObject
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
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $optionResolver */
        $optionResolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $optionResolver->expects($this->once())
            ->method('setRequired')
            ->with(['is_mobile'])
            ->willReturn($optionResolver);

        $optionResolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('is_mobile', ['boolean'])
            ->willReturn($optionResolver);

        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock('Oro\Component\Layout\ContextInterface');
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
