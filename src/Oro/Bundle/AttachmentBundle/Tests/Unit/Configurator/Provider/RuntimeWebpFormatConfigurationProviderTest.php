<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeContext;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeWebpFormatConfigurationProvider;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

class RuntimeWebpFormatConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    private RuntimeWebpFormatConfigurationProvider $provider;
    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    #[\Override]
    protected function setUp(): void
    {
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);
        $this->provider = new RuntimeWebpFormatConfigurationProvider($this->webpConfiguration);
    }

    public function testIsSupportedAlwaysReturnsTrue(): void
    {
        self::assertTrue($this->provider->isSupported('sample_filter'));
    }

    public static function emptyFormatDataProvider(): array
    {
        return [
            'without format key' => [['other_key' => 'value']],
            'empty format' => [['format' => '']],
            'null format' => [['format' => null]],
        ];
    }

    /**
     * @dataProvider emptyFormatDataProvider
     */
    public function testGetRuntimeConfigWithEmptyOrNullFormat(array $contextData): void
    {
        $context = new RuntimeContext($contextData);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        self::assertEquals([], $result);
    }

    public static function nonWebpFormatDataProvider(): array
    {
        return [
            'jpg uppercase' => ['JPG', 'jpg'],
            'png uppercase' => ['PNG', 'png'],
        ];
    }

    /**
     * @dataProvider nonWebpFormatDataProvider
     */
    public function testGetRuntimeConfigWithNonWebpFormat(string $inputFormat, string $expectedFormat): void
    {
        $context = new RuntimeContext(['format' => $inputFormat]);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        self::assertEquals(['format' => $expectedFormat], $result);
    }

    public static function webpFormatDataProvider(): array
    {
        return [
            'webp lowercase, not disabled' => ['webp', false, 85],
            'WEBP uppercase, not disabled' => ['WEBP', false, 90],
        ];
    }

    /**
     * @dataProvider webpFormatDataProvider
     */
    public function testGetRuntimeConfigWithWebpFormatAndNotDisabled(
        string $inputFormat,
        bool $isDisabled,
        int $quality
    ): void {
        $context = new RuntimeContext(['format' => $inputFormat]);

        $this->webpConfiguration->expects(self::once())
            ->method('isDisabled')
            ->willReturn($isDisabled);

        $this->webpConfiguration->expects(self::once())
            ->method('getWebpQuality')
            ->willReturn($quality);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        $expected = [
            'format' => 'webp',
            'quality' => $quality,
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetRuntimeConfigWithWebpFormatAndDisabled(): void
    {
        $context = new RuntimeContext(['format' => 'webp']);

        $this->webpConfiguration->expects(self::once())
            ->method('isDisabled')
            ->willReturn(true);

        $this->webpConfiguration->expects(self::never())
            ->method('getWebpQuality');

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        $expected = ['format' => 'webp'];
        self::assertEquals($expected, $result);
    }
}
