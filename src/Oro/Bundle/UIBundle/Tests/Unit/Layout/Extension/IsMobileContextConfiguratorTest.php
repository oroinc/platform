<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\UIBundle\Layout\Extension\IsMobileContextConfigurator;
use Oro\Bundle\UIBundle\Provider\UserAgent;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Component\Layout\ContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IsMobileContextConfiguratorTest extends TestCase
{
    private UserAgentProvider&MockObject $userAgentProvider;
    private UserAgent&MockObject $userAgent;
    private IsMobileContextConfigurator $isMobileContextConfigurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->userAgentProvider = $this->createMock(UserAgentProvider::class);
        $this->userAgent = $this->createMock(UserAgent::class);

        $this->isMobileContextConfigurator = new IsMobileContextConfigurator($this->userAgentProvider);
    }

    public function testConfigureContext(): void
    {
        $optionResolver = $this->createMock(OptionsResolver::class);
        $optionResolver->expects($this->once())
            ->method('setRequired')
            ->with(['is_mobile'])
            ->willReturn($optionResolver);

        $optionResolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('is_mobile', ['boolean'])
            ->willReturn($optionResolver);

        $context = $this->createMock(ContextInterface::class);
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
