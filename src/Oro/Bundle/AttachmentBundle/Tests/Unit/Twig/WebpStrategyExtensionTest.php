<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\AttachmentBundle\Twig\WebpStrategyExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class WebpStrategyExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private WebpStrategyExtension $extension;

    protected function setUp(): void
    {
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $serviceLocator = self::getContainerBuilder()
            ->add(WebpConfiguration::class, $this->webpConfiguration)
            ->getContainer($this);

        $this->extension = new WebpStrategyExtension($serviceLocator);
    }

    /**
     * @dataProvider isEnabledIfSupportedDataProvider
     */
    public function testIsIfSupportedWebpStrategyEnabled(bool $enabled): void
    {
        $this->webpConfiguration->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn($enabled);

        $result = self::callTwigFunction(
            $this->extension,
            'is_webp_enabled_if_supported',
            []
        );

        self::assertEquals($enabled, $result);
    }

    public function isEnabledIfSupportedDataProvider(): array
    {
        return [
            [
                'enabled' => false,
            ],
            [
                'enabled' => true,
            ],
        ];
    }
}
