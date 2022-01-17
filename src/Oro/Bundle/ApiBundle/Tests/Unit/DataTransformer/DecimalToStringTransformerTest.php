<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\DecimalToStringTransformer;

class DecimalToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DecimalToStringTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DecimalToStringTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?string $value, ?string $expected)
    {
        self::assertSame($expected, $this->transformer->transform($value, [], []));
    }

    public function transformDataProvider(): array
    {
        return [
            [null, null],
            ['123', '123'],
            ['-123', '-123'],
            ['+123', '123'],
            ['0123', '123'],
            ['123.00', '123'],
            ['123.456', '123.456'],
            ['123.45600', '123.456']
        ];
    }
}
