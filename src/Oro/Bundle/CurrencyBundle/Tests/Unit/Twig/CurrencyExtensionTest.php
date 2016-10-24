<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Twig;

use Twig_SimpleFilter;

use Oro\Bundle\CurrencyBundle\Tests\Unit\Utils\CurrencyNameHelperStub;
use Oro\Bundle\CurrencyBundle\Twig\CurrencyExtension;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class CurrencyExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencyExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\LocaleBundle\Formatter\NumberFormatter
     */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\CurrencyBundle\Provider\ViewTypeConfigProvider */
        $viewTypeProvider = $this
            ->getMockBuilder('Oro\Bundle\CurrencyBundle\Provider\ViewTypeConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new CurrencyExtension($this->formatter, $viewTypeProvider, new CurrencyNameHelperStub());
    }

    public function testGetFilters()
    {
        /** @var Twig_SimpleFilter[] $filters */
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        $availableFilters = ['oro_format_price', 'oro_localized_currency_name'];

        foreach ($filters as $filter) {
            $this->assertInstanceOf('Twig_SimpleFilter', $filter);
            $this->assertTrue(in_array($filter->getName(), $availableFilters, true));
        }
    }

    /**
     * @param Price $price
     * @param array $options
     * @param string $expected
     * @dataProvider formatCurrencyDataProvider
     */
    public function testFormatCurrency(Price $price, array $options, $expected)
    {
        $this->formatter->expects($this->once())->method('formatCurrency')
            ->with(
                $price->getValue(),
                $price->getCurrency(),
                $options['attributes'],
                $options['textAttributes'],
                $options['symbols'],
                $options['locale']
            )
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->extension->formatPrice($price, $options));
    }

    /**
     * @return array
     */
    public function formatCurrencyDataProvider()
    {
        return [
            '$1,234.5' => [
                'price' => new Price(1234.5, 'USD'),
                'options' => [
                    'attributes' => ['grouping_size' => 3],
                    'textAttributes' => ['grouping_separator_symbol' => ','],
                    'symbols' => ['symbols' => '$'],
                    'locale' => 'en_US'
                ],
                'expected' => '$1,234.5'
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oro_currency', $this->extension->getName());
    }
}
