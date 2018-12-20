<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\DataTransformer\PriceTransformer;

class PriceTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PriceTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new PriceTransformer();
    }

    public function testTransform()
    {
        $price = Price::create(100, 'USD');
        $this->assertSame($price, $this->transformer->transform($price));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param Price|null $data
     * @param Price|null $expected
     */
    public function testReverseTransform($data, $expected)
    {
        $this->assertSame($expected, $this->transformer->reverseTransform($data));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $zeroPrice = Price::create(0, 'USD');
        $price = Price::create(100, 'USD');
        $lessZeroPrice = Price::create('-1', 'USD');
        return [
            'zero price' => [
                'input' => $zeroPrice,
                'expected' => $zeroPrice
            ],
            'price' => [
                'input' => $price,
                'expected' => $price
            ],
            'null' => [
                'data' => null,
                'expected' => null
            ],
            'invalid price' => [
                'input' => 'string',
                'expected' => null
            ],
            'invalid price value' => [
                'input' => Price::create('price', 'USD'),
                'expected' => null
            ],
            'price value less than zero' => [
                'input' => $lessZeroPrice,
                'expected' => $lessZeroPrice
            ]
        ];
    }
}
