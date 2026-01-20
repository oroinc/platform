<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Bundle\LayoutBundle\Layout\Extension\ApiSvgConfigurator;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiSvgConfiguratorTest extends TestCase
{
    private ApiUrlResolver&MockObject $apiUrlResolver;
    private ApiSvgConfigurator $configurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->apiUrlResolver = $this->createMock(ApiUrlResolver::class);
        $this->configurator = new ApiSvgConfigurator($this->apiUrlResolver);
    }

    public function testConfigureContextWhenAbsoluteUrlsNotRequired(): void
    {
        $context = new LayoutContext();

        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(false);

        $this->configurator->configureContext($context);

        $context->resolve();
        self::assertFalse($context->get('is_svg_via_api'));
    }

    public function testConfigureContextWhenAbsoluteUrlsRequired(): void
    {
        $context = new LayoutContext();

        $this->apiUrlResolver->expects(self::once())
            ->method('shouldUseAbsoluteUrls')
            ->willReturn(true);

        $this->configurator->configureContext($context);

        $context->resolve();
        self::assertTrue($context->get('is_svg_via_api'));
    }
}
