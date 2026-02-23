<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\AttachmentBundle\Twig\WebpStrategyExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebpStrategyExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private WebpConfiguration&MockObject $webpConfiguration;
    private WebpStrategyExtension $extension;

    #[\Override]
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

        self::assertSame(
            $enabled,
            self::callTwigFunction($this->extension, 'is_webp_enabled_if_supported', [])
        );
    }

    public static function isEnabledIfSupportedDataProvider(): array
    {
        return [
            ['enabled' => false],
            ['enabled' => true]
        ];
    }
}
