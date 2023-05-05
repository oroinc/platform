<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigDefaultProvider;

class FilterRuntimeConfigDefaultProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getRuntimeConfigForFilterDataProvider
     */
    public function testGetRuntimeConfigForFilter(string $format, array $expectedConfig): void
    {
        $provider = new FilterRuntimeConfigDefaultProvider();

        self::assertEquals($expectedConfig, ($provider)->getRuntimeConfigForFilter('sample_filter', $format));
    }

    public function getRuntimeConfigForFilterDataProvider(): array
    {
        return [
            'empty format returns empty config' => ['format' => '', 'expectedConfig' => []],
            'webp format returns config with format option' => [
                'format' => 'webp',
                'expectedConfig' => ['format' => 'webp'],
            ],
        ];
    }
}
