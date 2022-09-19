<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\CurrencyBundle\Form\DataTransformer\MoneyValueTransformer;

class MoneyValueTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MoneyValueTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new MoneyValueTransformer();
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform(string $rawValue, string $expectedValue)
    {
        $this->assertSame($expectedValue, $this->transformer->reverseTransform($rawValue));
    }

    public function reverseTransformProvider(): array
    {
        return [
            'Not numeric' => [
                'rawValue'     => 'incorrect value',
                'expecedValue' => 'incorrect value'
            ],
            'Numeric value without decimal part' => [
                'rawValue'     => '100',
                'expecedValue' => '100.0000'
            ],
            'Decimal part less than scale of money field type' => [
                'rawValue'     => '100.4',
                'expecedValue' => '100.4000',
            ],
            'Decimal part bigger than scale of money field type' => [
                'rawValue'     => '100.45678',
                'expecedValue' => '100.45678',
            ]
        ];
    }
}
