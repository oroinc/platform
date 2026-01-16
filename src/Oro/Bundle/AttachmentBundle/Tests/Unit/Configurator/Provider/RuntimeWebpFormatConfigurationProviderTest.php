<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeContext;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeWebpFormatConfigurationProvider;
use Oro\Bundle\AttachmentBundle\Provider\WebpAwareFilterRuntimeConfigProvider;

class RuntimeWebpFormatConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    private RuntimeWebpFormatConfigurationProvider $provider;
    private WebpAwareFilterRuntimeConfigProvider|\PHPUnit\Framework\MockObject\MockObject $webpAwareProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->webpAwareProvider = $this->createMock(WebpAwareFilterRuntimeConfigProvider::class);
        $this->provider = new RuntimeWebpFormatConfigurationProvider($this->webpAwareProvider);
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

        $this->webpAwareProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with('sample_filter', $inputFormat)
            ->willReturn(['format' => $expectedFormat]);

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

        $expected = [
            'format' => 'webp',
            'quality' => $quality,
        ];

        $this->webpAwareProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with('sample_filter', $inputFormat)
            ->willReturn($expected);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        self::assertEquals($expected, $result);
    }

    public function testGetRuntimeConfigWithWebpFormatAndDisabled(): void
    {
        $context = new RuntimeContext(['format' => 'webp']);

        $expected = ['format' => 'webp'];

        $this->webpAwareProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with('sample_filter', 'webp')
            ->willReturn($expected);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        self::assertEquals($expected, $result);
    }
}
