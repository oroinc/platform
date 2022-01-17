<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\MoneyToStringTransformer;

class MoneyToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MoneyToStringTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new MoneyToStringTransformer();
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
            ['123', '123.0000'],
            ['123.00', '123.0000'],
            ['123.0000', '123.0000'],
            ['123.456', '123.4560'],
            ['123.4560', '123.4560'],
            ['123.4561', '123.4561'],
            ['123.4569', '123.4569'],
            ['123.45671', '123.4567'],
            ['123.45679', '123.4567'],
            ['123.45611', '123.4561'],
            ['123.45619', '123.4561'],
            ['123.45691', '123.4569'],
            ['123.45699', '123.4569']
        ];
    }
}
